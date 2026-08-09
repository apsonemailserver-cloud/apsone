<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

use App\Http\Controllers\Traits\PreservesIndexState;

class RoleController extends Controller
{
    use PreservesIndexState;

    public function index(Request $request)
    {
        if ($redirect = $this->checkIndexState($request, 'roles', '#^/roles(/\d+)?(/edit|/permissions)?$|/roles/create#')) {
            return $redirect;
        }

        $query = Role::withCount('permissions')->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $perPage = (int) $request->input('per_page', 10);
        $roles = $query->paginate($perPage)->withQueryString();
        
        // Count users per role
        $allUsers = User::with('roleRelation')->get();
        $userCounts = [];
        foreach ($allUsers as $u) {
            $rName = $u->roleRelation->name ?? '-';
            if (!isset($userCounts[$rName])) {
                $userCounts[$rName] = 0;
            }
            $userCounts[$rName]++;
        }

        $modules = Permission::modules();
        $actions = Permission::actions();
        $allPermissions = Permission::all()->groupBy('module');

        return view('role.index', compact('roles', 'userCounts', 'modules', 'actions', 'allPermissions'));
    }

    public function create()
    {
        return view('role.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'description' => 'nullable|string|max:255',
        ]);

        $role = Role::create([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        Alert::success('Berhasil', "Role '{$role->name}' berhasil dibuat.");
        return redirect()->route('roles.index');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        return view('role.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string|max:255',
        ]);

        $role->update([
            'description' => $validated['description'] ?? null,
        ]);

        Alert::success('Berhasil', "Data role '{$role->name}' berhasil diperbarui.");
        return redirect()->route('roles.index');
    }

    public function permissions($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $modules = Permission::modules();
        $actions = Permission::actions();
        $permissionsByModule = Permission::all()->groupBy('module');
        $assignedPermissionIds = $role->permissions->pluck('id')->toArray();

        // Get employees list: SORT ACTIVE (has_role = true) FIRST, then by fullname
        $employees = User::with(['roleRelation', 'jobTitle'])
            ->get()
            ->map(function($user) use ($role) {
                $user->has_role = ($user->role_id == $role->id);
                return $user;
            })
            ->sortBy([
                ['has_role', 'desc'],
                ['fullname', 'asc']
            ])
            ->values();

        $activeEmployeeCount = $employees->where('has_role', true)->count();

        return view('role.permissions', compact(
            'role', 
            'modules', 
            'actions', 
            'permissionsByModule', 
            'assignedPermissionIds', 
            'employees',
            'activeEmployeeCount'
        ));
    }

    public function updatePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($role->name === 'Admin') {
            $role->permissions()->sync(Permission::pluck('id')->toArray());
        } else {
            $role->permissions()->sync($validated['permissions'] ?? []);
        }

        // AJAX / fetch request — return JSON so the page can stay and show toast
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Hak akses role '{$role->name}' berhasil diperbarui.",
                'count'   => $role->permissions()->count(),
            ]);
        }

        // Normal form submission — redirect with flash alert
        Alert::success('Berhasil', "Hak akses role '{$role->name}' berhasil diperbarui.");
        return redirect()->route('roles.index');
    }

    public function toggleUserRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);

        if ($user->role_id == $role->id) {
            $user->role_id = null;
            $hasRole = false;
        } else {
            $user->role_id = $role->id;
            $hasRole = true;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Role karyawan berhasil diperbarui.',
            'has_role' => $hasRole
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system || in_array($role->name, ['Admin', 'Manager', 'Staff'])) {
            Alert::error('Gagal', "Role sistem '{$role->name}' tidak dapat dihapus.");
            return redirect()->back();
        }

        $role->permissions()->detach();
        $role->delete();

        Alert::success('Berhasil', "Role '{$role->name}' berhasil dihapus.");
        return redirect()->route('roles.index');
    }
}
