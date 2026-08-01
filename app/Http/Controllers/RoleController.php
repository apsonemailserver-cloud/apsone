<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount('permissions')->get();
        
        // Count users per role
        $allUsers = User::select('id', 'fullname', 'role')->get();
        $userCounts = [];
        foreach ($allUsers as $u) {
            $rList = array_map('trim', explode(',', (string)$u->role));
            foreach ($rList as $rName) {
                if (!isset($userCounts[$rName])) {
                    $userCounts[$rName] = 0;
                }
                $userCounts[$rName]++;
            }
        }

        $modules = Permission::modules();
        $actions = Permission::actions();
        $allPermissions = Permission::all()->groupBy('module');

        return view('role.index', compact('roles', 'userCounts', 'modules', 'actions', 'allPermissions'));
    }

    public function create()
    {
        $modules = Permission::modules();
        $actions = Permission::actions();
        $permissionsByModule = Permission::all()->groupBy('module');

        return view('role.create', compact('modules', 'actions', 'permissionsByModule'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'label' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => trim($validated['name']),
            'label' => $validated['label'] ?: trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        Alert::success('Berhasil', "Role '{$role->name}' berhasil dibuat.");
        return redirect()->route('roles.index');
    }

    public function edit($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $modules = Permission::modules();
        $actions = Permission::actions();
        $permissionsByModule = Permission::all()->groupBy('module');
        $assignedPermissionIds = $role->permissions->pluck('id')->toArray();

        // Get employees list: SORT ACTIVE (has_role = true) FIRST, then by fullname
        $employees = User::select('id', 'fullname', 'job_title', 'station', 'role')
            ->get()
            ->map(function($user) use ($role) {
                $userRoles = array_map('trim', explode(',', (string)$user->role));
                $user->has_role = in_array($role->name, $userRoles);
                return $user;
            })
            ->sortBy([
                ['has_role', 'desc'],
                ['fullname', 'asc']
            ])
            ->values();

        $activeEmployeeCount = $employees->where('has_role', true)->count();

        return view('role.edit', compact(
            'role', 
            'modules', 
            'actions', 
            'permissionsByModule', 
            'assignedPermissionIds', 
            'employees',
            'activeEmployeeCount'
        ));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($role->name === 'Admin') {
            // Admin always has all permissions
            $role->permissions()->sync(Permission::pluck('id')->toArray());
        } else {
            $role->permissions()->sync($validated['permissions'] ?? []);
        }

        Alert::success('Berhasil', "Hak akses role '{$role->name}' berhasil diperbarui.");
        return redirect()->route('roles.index');
    }

    public function toggleUserRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);

        $userRoles = array_filter(array_map('trim', explode(',', (string)$user->role)));
        
        if (in_array($role->name, $userRoles)) {
            // Detach role
            $userRoles = array_diff($userRoles, [$role->name]);
        } else {
            // Attach role
            $userRoles[] = $role->name;
        }

        $user->role = implode(', ', array_unique($userRoles));
        $user->save();

        $hasRole = in_array($role->name, array_map('trim', explode(',', (string)$user->role)));

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
