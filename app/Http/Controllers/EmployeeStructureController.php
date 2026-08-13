<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeStructureController extends Controller
{
    /**
     * Map role name to hierarchy level number matching the user diagram:
     * 1 = Administrator
     * 2 = HOAS (Head Of Airport Service)
     * 3 = SPV / Admin Station
     * 4 = Leader
     * 5 = Staff Lapangan
     */
    protected static function resolveLevel(string $roleName): int
    {
        $role = strtolower($roleName);
        if (str_contains($role, 'administrator') || $role === 'admin') return 1;
        if (str_contains($role, 'head of airport') || str_contains($role, 'hoas')) return 2;
        if (str_contains($role, 'spv') || str_contains($role, 'supervisor')) return 3;
        if (str_contains($role, 'leader') || str_contains($role, 'head')) return 4;
        return 5; // Staff Lapangan
    }

    /**
     * Return level color palette & label matching user diagram.
     */
    protected static function resolveLevelColor(int $level): array
    {
        return match ($level) {
            1 => ['bg' => '#6366f1', 'text' => '#ffffff', 'badge' => 'primary', 'label' => 'Administrator'],
            2 => ['bg' => '#ec4899', 'text' => '#ffffff', 'badge' => 'danger',  'label' => 'HOAS'],
            3 => ['bg' => '#3b82f6', 'text' => '#ffffff', 'badge' => 'info',    'label' => 'SPV / Admin Station'],
            4 => ['bg' => '#f59e0b', 'text' => '#ffffff', 'badge' => 'warning', 'label' => 'Leader'],
            default => ['bg' => '#10b981', 'text' => '#ffffff', 'badge' => 'success', 'label' => 'Staff Lapangan'],
        };
    }

    /**
     * Display employee hierarchy structure page.
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        abort_unless($authUser->canAccess('user', 'view'), 403);

        // Auto-run simulation if no pic_id is set across the database yet
        if (User::whereNotNull('pic_id')->count() === 0) {
            static::runAutoSimulation();
        }

        $viewMode        = $request->query('view_mode', 'bagan');
        $selectedStation = $request->query('station');
        $search          = trim($request->query('search', ''));
        $levelFilter     = $request->query('level', '');

        // Available stations
        $isFullAccess = $authUser->hasRole(['Admin', 'Head Of Airport Service']) || ($authUser->station === 'Ho');
        if ($isFullAccess) {
            $stations = Station::orderBy('name', 'asc')->get();
            if (!$selectedStation) {
                $selectedStation = ($authUser->station && $authUser->station !== 'Ho')
                    ? $authUser->station
                    : ($stations->first()->code ?? 'CGK');
            }
        } else {
            $stations = Station::where('code', $authUser->station)->orderBy('name', 'asc')->get();
            if (!$selectedStation) {
                $selectedStation = $authUser->station;
            }
        }

        // All users for modal select
        $allUsersQuery = User::with(['jobTitle', 'roleRelation'])->where('is_active', true)->orderBy('fullname', 'asc');
        if (!$isFullAccess && $authUser->station) {
            $allUsersQuery->where('station', $authUser->station);
        }
        $allUsers = $allUsersQuery->get();

        // ── LIST VIEW ──────────────────────────────────────────
        if ($viewMode === 'list') {
            $query = User::with(['pic', 'subordinates', 'jobTitle', 'roleRelation', 'stationRelation'])
                ->where('is_active', true);

            if (!$isFullAccess && $authUser->station) {
                $query->where('station', $authUser->station);
            } elseif ($selectedStation) {
                $query->where('station', $selectedStation);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('fullname', 'like', "%{$search}%")
                        ->orWhereHas('jobTitle', fn($jtq) => $jtq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('roleRelation', fn($rq) => $rq->where('name', 'like', "%{$search}%"));
                });
            }

            $users = $query->orderBy('fullname', 'asc')->paginate(15)->withQueryString();

            return view('employee_structure.index', compact(
                'viewMode', 'selectedStation', 'search', 'levelFilter',
                'stations', 'allUsers', 'users'
            ));
        }

        // ── BAGAN VIEW ─────────────────────────────────────────
        $usersQuery = User::with(['pic', 'jobTitle', 'roleRelation', 'stationRelation'])
            ->where('is_active', true);

        if (!$isFullAccess && $authUser->station) {
            $usersQuery->where('station', $authUser->station);
        } elseif ($selectedStation) {
            $usersQuery->where('station', $selectedStation);
        }

        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhereHas('jobTitle', fn($jtq) => $jtq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('roleRelation', fn($rq) => $rq->where('name', 'like', "%{$search}%"));
            });
        }

        $allActiveUsers = $usersQuery->get();

        // Attach level metadata to each user object
        foreach ($allActiveUsers as $u) {
            $roleName       = $u->getRoleName() ?? 'Staff Lapangan';
            $level          = static::resolveLevel($roleName);
            $palette        = static::resolveLevelColor($level);
            $u->level_num   = $level;
            $u->level_color = $palette['bg'];
            $u->level_text  = $palette['text'];
            $u->level_badge = $palette['badge'];
            $u->level_label = $palette['label'];
        }

        $userMap     = $allActiveUsers->keyBy('id');
        $tree        = [];
        $childrenMap = [];

        foreach ($allActiveUsers as $u) {
            $picId = $u->pic_id;
            if ($picId && isset($userMap[$picId]) && $picId !== $u->id) {
                $childrenMap[$picId][] = $u;
            } else {
                $tree[] = $u;
            }
        }

        // Recursive tree builder — attaches children & total subordinate count
        $buildTree = function ($nodes) use (&$buildTree, &$childrenMap) {
            $result = [];
            foreach ($nodes as $node) {
                $nodeChildren        = $childrenMap[$node->id] ?? [];
                $node->children_tree = $buildTree($nodeChildren);

                $countAll = function ($n) use (&$countAll) {
                    $c = count($n->children_tree);
                    foreach ($n->children_tree as $ch) {
                        $c += $countAll($ch);
                    }
                    return $c;
                };
                $node->total_sub_count = $countAll($node);
                $result[]            = $node;
            }
            return $result;
        };

        $treeData           = $buildTree($tree);
        $totalCollaborators = $allActiveUsers->count();

        return view('employee_structure.index', compact(
            'viewMode', 'selectedStation', 'search', 'levelFilter',
            'stations', 'allUsers', 'treeData', 'totalCollaborators'
        ));
    }

    /**
     * Update superior (Atasan Langsung) for an employee.
     */
    public function updateSuperior(Request $request)
    {
        $authUser = Auth::user();
        abort_unless($authUser->canAccess('user', 'edit') || $authUser->isAdmin(), 403);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pic_id'  => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id;
        $picId  = $request->pic_id;

        if ($userId === $picId) {
            return back()->with('error', 'Karyawan tidak bisa menjadi atasan untuk dirinya sendiri.');
        }

        // Prevent circular assignment
        if ($picId) {
            $curr = User::find($picId);
            while ($curr && $curr->pic_id) {
                if ($curr->pic_id === $userId) {
                    return back()->with('error', 'Tidak dapat menetapkan atasan secara sirkular.');
                }
                $curr = User::find($curr->pic_id);
            }
        }

        $user         = User::findOrFail($userId);
        $user->pic_id = $picId ?: null;
        if ($picId) {
            $superior      = User::find($picId);
            $user->manager = $superior ? $superior->fullname : null;
        } else {
            $user->manager = null;
        }
        $user->save();

        return back()->with('success', 'Atasan langsung berhasil diperbarui!');
    }

    /**
     * Auto-simulate employee hierarchy.
     */
    public function simulateHierarchy(Request $request)
    {
        $authUser = Auth::user();
        abort_unless($authUser->canAccess('user', 'edit') || $authUser->isAdmin(), 403);

        static::runAutoSimulation($request->input('station'));
        return back()->with('success', 'Simulasi hirarki karyawan berhasil disinkronkan!');
    }

    /**
     * Core simulation logic matching exact user diagram:
     * Level 1: Administrator (Top Root)
     * Level 2: HOAS per Station (HOAS CGK, HOAS SUB, HOAS KNO) -> pic_id = Administrator
     * Level 3: SPV & Admin Station per Station -> pic_id = HOAS
     * Level 4: Leader per Station -> pic_id = SPV (or HOAS)
     * Level 5: Staff Lapangan per Station -> pic_id = Leader (or SPV / HOAS)
     */
    public static function runAutoSimulation(?string $stationCode = null)
    {
        DB::transaction(function () use ($stationCode) {
            $query = User::with(['roleRelation', 'jobTitle'])->where('is_active', true);
            if ($stationCode) {
                $query->where('station', $stationCode);
            }
            $allUsers = $query->get();

            // 1. TOP ROOT: Administrator (ID 0021225 or main Admin in HO)
            $adminRoot = $allUsers->first(fn($u) =>
                $u->id === '0021225' ||
                (($u->station === 'HO' || $u->station === 'CGK') && strtolower($u->getRoleName() ?? '') === 'admin')
            ) ?? $allUsers->first(fn($u) => str_contains(strtolower($u->getRoleName() ?? ''), 'admin')) ?? $allUsers->first();

            if ($adminRoot) {
                $adminRoot->pic_id  = null;
                $adminRoot->manager = null;
                $adminRoot->save();
            }

            // 2. LEVEL 2: HOAS per Station (Head Of Airport Service)
            $hoasUsers = $allUsers->filter(fn($u) =>
                str_contains(strtolower($u->getRoleName() ?? ''), 'head of airport')
            );

            foreach ($hoasUsers as $hoas) {
                if ($adminRoot && $hoas->id !== $adminRoot->id) {
                    $hoas->pic_id  = $adminRoot->id;
                    $hoas->manager = $adminRoot->fullname;
                    $hoas->save();
                }
            }

            // Group users by station
            $usersByStation = $allUsers->groupBy(fn($u) => $u->station ?: 'CGK');

            foreach ($usersByStation as $stCode => $stUsers) {
                // Find Station HOAS or fallback to adminRoot
                $stHoas = $stUsers->first(fn($u) =>
                    str_contains(strtolower($u->getRoleName() ?? ''), 'head of airport')
                ) ?? $adminRoot;

                // 3A. LEVEL 3: Admin Station (Role Admin in station except adminRoot)
                $adminStations = $stUsers->filter(fn($u) =>
                    $u->id !== ($adminRoot->id ?? null)
                    && !$hoasUsers->contains('id', $u->id)
                    && (strtolower($u->getRoleName() ?? '') === 'admin' || str_contains(strtolower($u->jobTitle->name ?? ''), 'office'))
                );

                foreach ($adminStations as $ast) {
                    if ($stHoas && $ast->id !== $stHoas->id) {
                        $ast->pic_id  = $stHoas->id;
                        $ast->manager = $stHoas->fullname;
                        $ast->save();
                    }
                }

                // 3B. LEVEL 3: SPV (Supervisors)
                $spvs = $stUsers->filter(fn($u) =>
                    $u->id !== ($adminRoot->id ?? null)
                    && !$hoasUsers->contains('id', $u->id)
                    && !$adminStations->contains('id', $u->id)
                    && (str_contains(strtolower($u->getRoleName() ?? ''), 'spv')
                        || str_contains(strtolower($u->getRoleName() ?? ''), 'supervisor'))
                );

                foreach ($spvs as $spv) {
                    if ($stHoas && $spv->id !== $stHoas->id) {
                        $spv->pic_id  = $stHoas->id;
                        $spv->manager = $stHoas->fullname;
                        $spv->save();
                    }
                }

                // 4. LEVEL 4: Leaders
                $leaders = $stUsers->filter(fn($u) =>
                    $u->id !== ($adminRoot->id ?? null)
                    && !$hoasUsers->contains('id', $u->id)
                    && !$adminStations->contains('id', $u->id)
                    && !$spvs->contains('id', $u->id)
                    && (str_contains(strtolower($u->getRoleName() ?? ''), 'leader')
                        || str_contains(strtolower($u->getRoleName() ?? ''), 'head'))
                );

                foreach ($leaders as $idx => $ldr) {
                    $parent = $spvs->isNotEmpty()
                        ? $spvs->values()->get($idx % $spvs->count())
                        : $stHoas;
                    if ($parent && $ldr->id !== $parent->id) {
                        $ldr->pic_id  = $parent->id;
                        $ldr->manager = $parent->fullname;
                        $ldr->save();
                    }
                }

                // 5. LEVEL 5: Staff Lapangan
                $staff = $stUsers->filter(fn($u) =>
                    $u->id !== ($adminRoot->id ?? null)
                    && !$hoasUsers->contains('id', $u->id)
                    && !$adminStations->contains('id', $u->id)
                    && !$spvs->contains('id', $u->id)
                    && !$leaders->contains('id', $u->id)
                );

                foreach ($staff as $idx => $stf) {
                    if ($leaders->isNotEmpty()) {
                        $parent = $leaders->values()->get($idx % $leaders->count());
                    } elseif ($spvs->isNotEmpty()) {
                        $parent = $spvs->values()->get($idx % $spvs->count());
                    } else {
                        $parent = $stHoas;
                    }
                    if ($parent && $stf->id !== $parent->id) {
                        $stf->pic_id  = $parent->id;
                        $stf->manager = $parent->fullname;
                        $stf->save();
                    }
                }
            }
        });
    }
}
