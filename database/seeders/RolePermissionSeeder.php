<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = Permission::modules();
        $actions = Permission::actions();
        $moduleActionsMap = Permission::moduleActionsMap();

        // 1. Create only valid permissions per module
        $allPermissionIds = [];
        foreach ($modules as $modKey => $modLabel) {
            $validActions = $moduleActionsMap[$modKey] ?? array_keys($actions);
            foreach ($validActions as $actKey) {
                $actLabel = $actions[$actKey] ?? ucfirst($actKey);
                $permName = "{$modKey}.{$actKey}";
                $perm = Permission::firstOrCreate(
                    ['name' => $permName],
                    [
                        'module' => $modKey,
                        'action' => $actKey,
                        'label' => "{$modLabel} - {$actLabel}",
                    ]
                );
                $allPermissionIds[$permName] = $perm->id;
            }
        }

        // 2. Discover roles from users table + default system roles
        $existingRoles = [];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $existingRoles = User::whereNotNull('role')
                ->where('role', '!=', '')
                ->pluck('role')
                ->flatMap(function ($r) {
                    return array_map('trim', explode(',', $r));
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $existingRoles = Role::pluck('name')->toArray();
        }

        $defaultRoles = [
            'Admin' => 'Administrator dengan akses penuh sistem',
            'SPV Bge' => 'Supervisor Baggage Handling',
            'SPV Apron' => 'Supervisor Apron / Ramp Area',
            'Leader Bge' => 'Team Leader Baggage',
            'Ass Leader Bge' => 'Assistant Leader Baggage',
            'Leader Apron' => 'Team Leader Apron',
            'Ass Leader Apron' => 'Assistant Leader Apron',
            'Porter Bge' => 'Porter Staff Baggage',
            'Porter Apron' => 'Porter Staff Apron',
            'HSE' => 'Health, Safety, & Environment Officer',
            'Head Of Airport Service' => 'Head of Airport Services',
            'Manager' => 'Manager Operasional',
            'Staff' => 'Staf Operasional',
        ];

        $rolesToCreate = array_unique(array_merge(array_keys($defaultRoles), $existingRoles));

        foreach ($rolesToCreate as $roleName) {
            $desc = $defaultRoles[$roleName] ?? "Role {$roleName}";
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                [
                    'description' => $desc,
                    'is_system' => in_array($roleName, ['Admin', 'Manager', 'Staff']),
                ]
            );

            // Assign default permissions to roles
            if ($roleName === 'Admin') {
                $role->permissions()->sync(array_values($allPermissionIds));
            } else {
                $rolePerms = [];

                // Standard View access for operational & menu features (excluding Admin-only modules)
                foreach (['dashboard', 'profile', 'assignment', 'attendance', 'overtime', 'schedule', 'shift', 'document', 'training', 'leave', 'announcement'] as $mod) {
                    if (isset($allPermissionIds["{$mod}.view"])) {
                        $rolePerms[] = $allPermissionIds["{$mod}.view"];
                    }
                }

                // Self-service creation & editing for Staff & Porter roles
                foreach (['attendance', 'overtime', 'leave', 'profile'] as $mod) {
                    foreach (['create', 'edit'] as $act) {
                        if (isset($allPermissionIds["{$mod}.{$act}"])) {
                            $rolePerms[] = $allPermissionIds["{$mod}.{$act}"];
                        }
                    }
                }

                // Leaders / SPVs / Managers get extended permissions (create, edit, approve, export)
                if (str_contains($roleName, 'SPV') || str_contains($roleName, 'Leader') || in_array($roleName, ['Manager', 'Head Of Airport Service', 'HSE'])) {
                    foreach (['assignment', 'attendance', 'overtime', 'schedule', 'leave', 'training', 'document', 'station', 'user', 'blacklist'] as $mod) {
                        foreach (['create', 'edit', 'approve', 'export'] as $act) {
                            if (isset($allPermissionIds["{$mod}.{$act}"])) {
                                $rolePerms[] = $allPermissionIds["{$mod}.{$act}"];
                            }
                        }
                    }
                }

                // Head Of Airport Service gets view access to Master Cuti
                if ($roleName === 'Head Of Airport Service' && isset($allPermissionIds['master_leave.view'])) {
                    $rolePerms[] = $allPermissionIds['master_leave.view'];
                }

                $role->permissions()->sync(array_unique($rolePerms));
            }
        }
    }
}
