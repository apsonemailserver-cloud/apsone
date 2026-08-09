<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\LeaveRule;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class MasterLeaveController extends Controller
{
    protected LeaveQuotaService $quotaService;

    public function __construct(LeaveQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    private function checkViewPermission(): void
    {
        $user = auth()->user();
        if (!$user->canAccess('master_leave', 'view') && !$user->isAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melihat Master Cuti.');
        }
    }

    private function checkManagePermission(): void
    {
        $user = auth()->user();
        if (!$user->canAccess('master_leave', 'edit') && !$user->canAccess('master_leave', 'create') && !$user->isAdmin()) {
            abort(403, 'Akses ditolak. Hanya Admin yang berhak mengubah Master Cuti.');
        }
    }

    /**
     * Display a listing of leave types and rules.
     */
    public function index(Request $request)
    {
        $this->checkViewPermission();

        $query = LeaveType::with('rules')->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $perPage = (int) $request->input('per_page', 10);
        $leaveTypes = $query->paginate($perPage)->withQueryString();

        return view('master_leaves.index', compact('leaveTypes'));
    }

    /**
     * Show the form for creating a new leave type.
     */
    public function createType()
    {
        $this->checkManagePermission();
        return view('master_leaves.create');
    }

    /**
     * Store a newly created leave type.
     */
    public function storeType(Request $request)
    {
        $this->checkManagePermission();
        $request->merge([
            'is_unlimited' => $request->boolean('is_unlimited') ? 1 : 0,
        ]);
        $request->validate([
            'name' => 'required|string|max:100',
            'default_quota' => 'required|integer|min:0',
            'gender_restriction' => 'required|string|in:All,Male,Female',
            'is_unlimited' => 'required|boolean',
        ]);

        LeaveType::create($request->only([
            'name',
            'default_quota',
            'gender_restriction',
            'is_unlimited',
        ]));

        // Sync balances for all users with the new type
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Tipe Cuti baru berhasil ditambahkan dan saldo seluruh karyawan telah diperbarui.');
        return redirect()->route('master_leaves.index');
    }

    /**
     * Show the form for editing the specified leave type.
     */
    public function editType($id)
    {
        $this->checkManagePermission();
        $leaveType = LeaveType::findOrFail($id);
        return view('master_leaves.edit', compact('leaveType'));
    }

    /**
     * Update the specified leave type.
     */
    public function updateType(Request $request, $id)
    {
        $this->checkManagePermission();
        $leaveType = LeaveType::findOrFail($id);

        $request->merge([
            'is_unlimited' => $request->boolean('is_unlimited') ? 1 : 0,
            'is_active' => $request->boolean('is_active') ? 1 : 0,
        ]);

        $request->validate([
            'name' => 'required|string|max:100',
            'default_quota' => 'required|integer|min:0',
            'gender_restriction' => 'required|string|in:All,Male,Female',
            'is_unlimited' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $leaveType->update($request->only([
            'name',
            'default_quota',
            'gender_restriction',
            'is_unlimited',
            'is_active',
        ]));

        // Sync balances for all users
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Tipe Cuti berhasil diperbarui dan saldo karyawan telah disinkronisasikan.');
        return redirect()->route('master_leaves.index');
    }

    /**
     * Delete the specified leave type.
     */
    public function destroyType($id)
    {
        $this->checkManagePermission();
        $leaveType = LeaveType::findOrFail($id);
        $leaveType->delete();

        Alert::success('Berhasil', 'Tipe Cuti berhasil dihapus.');
        return redirect()->route('master_leaves.index');
    }

    /**
     * Show dedicated rules page for a specific leave type.
     */
    public function rules(Request $request, $typeId)
    {
        $this->checkViewPermission();
        $leaveType = LeaveType::findOrFail($typeId);
        $rules = LeaveRule::where('leave_type_id', $typeId)->orderBy('min_tenure_years')->get();

        return view('master_leaves.rules', compact('leaveType', 'rules'));
    }

    /**
     * Show the form for creating a new leave rule.
     */
    public function createRule($typeId)
    {
        $this->checkManagePermission();
        $leaveType = LeaveType::findOrFail($typeId);

        return view('master_leaves.rules_create', compact('leaveType'));
    }

    /**
     * Show the form for editing the specified leave rule.
     */
    public function editRule($id)
    {
        $this->checkManagePermission();
        $rule = LeaveRule::findOrFail($id);
        $leaveType = LeaveType::findOrFail($rule->leave_type_id);

        return view('master_leaves.rules_edit', compact('rule', 'leaveType'));
    }

    /**
     * Store a newly created leave rule for a type.
     */
    public function storeRule(Request $request, $typeId)
    {
        $this->checkManagePermission();
        $request->validate([
            'min_tenure_years' => 'required|integer|min:0',
            'max_tenure_years' => 'nullable|integer|gt:min_tenure_years',
            'quota_days' => 'required|integer|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        LeaveRule::create([
            'leave_type_id' => $typeId,
            'min_tenure_years' => $request->min_tenure_years,
            'max_tenure_years' => $request->max_tenure_years,
            'quota_days' => $request->quota_days,
            'description' => $request->description,
        ]);

        // Sync balances for all users
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Aturan Masa Kerja baru berhasil ditambahkan.');
        return redirect()->route('master_leaves.rules', $typeId);
    }

    /**
     * Update the specified leave rule.
     */
    public function updateRule(Request $request, $id)
    {
        $this->checkManagePermission();
        $rule = LeaveRule::findOrFail($id);

        $request->validate([
            'min_tenure_years' => 'required|integer|min:0',
            'max_tenure_years' => 'nullable|integer|gt:min_tenure_years',
            'quota_days' => 'required|integer|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $rule->update($request->all());

        // Sync balances for all users
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Aturan Masa Kerja berhasil diperbarui.');
        return redirect()->route('master_leaves.rules', $rule->leave_type_id);
    }

    /**
     * Delete the specified leave rule.
     */
    public function destroyRule($id)
    {
        $this->checkManagePermission();
        $rule = LeaveRule::findOrFail($id);
        $typeId = $rule->leave_type_id;
        $rule->delete();

        // Sync balances for all users
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Aturan Masa Kerja berhasil dihapus.');
        return redirect()->route('master_leaves.rules', $typeId);
    }

    private function checkSyncPermission(): void
    {
        $user = auth()->user();
        if (!$user->canAccess('master_leave', 'sync') && !$user->isAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengsinkronisasi Master Cuti.');
        }
    }

    /**
     * Force synchronize all leave balances.
     */
    public function syncBalances()
    {
        $this->checkSyncPermission();
        $this->quotaService->syncAllBalances(date('Y'));
        Alert::success('Berhasil', 'Semua saldo cuti karyawan telah disinkronisasikan ulang.');
        return redirect()->back();
    }
}
