<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminStationPermissionsSeeder extends Seeder
{
    /**
     * Roles that serve as "Admin Station" (SPV / Leader level).
     * These roles should have access to User Management, Station View, and Shift Create.
     */
    private array $adminStationRoles = [
        'SPV Bge',
        'SPV Apron',
        'Leader Bge',
        'Leader Apron',
        'Ass Leader Bge',
        'Ass Leader Apron',
        'Leader Aircraft Interior Exterior Cleaning',
        'Leader Porter Apron',
        'HSE',
        'Manager',
    ];

    /**
     * Permissions to add to Admin Station roles.
     */
    private array $adminStationPermissions = [
        'user.view',
        'station.view',
        'shift.create',
    ];

    public function run(): void
    {
        // Ensure all required permissions exist in the permissions table
        $this->ensurePermissionsExist();

        // Grant permissions to Admin Station roles
        foreach ($this->adminStationRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                $this->command->warn("Role '{$roleName}' not found, skipping.");
                continue;
            }

            $permissionsToAttach = Permission::whereIn('name', $this->adminStationPermissions)->get();
            $existingPermIds = $role->permissions()->pluck('permissions.id')->toArray();

            foreach ($permissionsToAttach as $permission) {
                if (! in_array($permission->id, $existingPermIds)) {
                    $role->permissions()->attach($permission->id);
                    $this->command->info("  + {$roleName}: granted '{$permission->name}'");
                }
            }
        }

        $this->command->info('AdminStationPermissionsSeeder completed.');
    }

    private function ensurePermissionsExist(): void
    {
        $allPerms = [
            'shift.create' => 'Create new work shifts',
            'station.view' => 'View station list and monitoring',
            'user.view'    => 'View staff / user list',
        ];

        foreach ($allPerms as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }
    }
}
