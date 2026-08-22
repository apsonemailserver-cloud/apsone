<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $newPermissions = [
            // Employees
            ['name' => 'employee.view',   'module' => 'employee',  'action' => 'view',   'label' => 'Employees - Lihat / Akses'],
            ['name' => 'employee.create', 'module' => 'employee',  'action' => 'create', 'label' => 'Employees - Tambah / Buat'],
            ['name' => 'employee.edit',   'module' => 'employee',  'action' => 'edit',   'label' => 'Employees - Edit / Ubah'],
            ['name' => 'employee.delete', 'module' => 'employee',  'action' => 'delete', 'label' => 'Employees - Hapus'],
            ['name' => 'employee.export', 'module' => 'employee',  'action' => 'export', 'label' => 'Employees - Export / Cetak PDF'],

            // Job Titles
            ['name' => 'job_title.view',   'module' => 'job_title', 'action' => 'view',   'label' => 'Job Titles - Lihat / Akses'],
            ['name' => 'job_title.create', 'module' => 'job_title', 'action' => 'create', 'label' => 'Job Titles - Tambah / Buat'],
            ['name' => 'job_title.edit',   'module' => 'job_title', 'action' => 'edit',   'label' => 'Job Titles - Edit / Ubah'],
            ['name' => 'job_title.delete', 'module' => 'job_title', 'action' => 'delete', 'label' => 'Job Titles - Hapus'],

            // Units
            ['name' => 'unit.view',   'module' => 'unit', 'action' => 'view',   'label' => 'Units - Lihat / Akses'],
            ['name' => 'unit.create', 'module' => 'unit', 'action' => 'create', 'label' => 'Units - Tambah / Buat'],
            ['name' => 'unit.edit',   'module' => 'unit', 'action' => 'edit',   'label' => 'Units - Edit / Ubah'],
            ['name' => 'unit.delete', 'module' => 'unit', 'action' => 'delete', 'label' => 'Units - Hapus'],

            // Sub Units
            ['name' => 'sub_unit.view',   'module' => 'sub_unit', 'action' => 'view',   'label' => 'Sub Units - Lihat / Akses'],
            ['name' => 'sub_unit.create', 'module' => 'sub_unit', 'action' => 'create', 'label' => 'Sub Units - Tambah / Buat'],
            ['name' => 'sub_unit.edit',   'module' => 'sub_unit', 'action' => 'edit',   'label' => 'Sub Units - Edit / Ubah'],
            ['name' => 'sub_unit.delete', 'module' => 'sub_unit', 'action' => 'delete', 'label' => 'Sub Units - Hapus'],

            // Clusters
            ['name' => 'cluster.view',   'module' => 'cluster', 'action' => 'view',   'label' => 'Clusters - Lihat / Akses'],
            ['name' => 'cluster.create', 'module' => 'cluster', 'action' => 'create', 'label' => 'Clusters - Tambah / Buat'],
            ['name' => 'cluster.edit',   'module' => 'cluster', 'action' => 'edit',   'label' => 'Clusters - Edit / Ubah'],
            ['name' => 'cluster.delete', 'module' => 'cluster', 'action' => 'delete', 'label' => 'Clusters - Hapus'],
        ];

        foreach ($newPermissions as $pData) {
            Permission::firstOrCreate(
                ['name' => $pData['name']],
                [
                    'module' => $pData['module'],
                    'action' => $pData['action'],
                    'label'  => $pData['label'],
                ]
            );
        }

        // Attach all system permissions to the Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $allPermIds = Permission::pluck('id')->toArray();
            $adminRole->permissions()->sync($allPermIds);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permNames = [
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.export',
            'job_title.view', 'job_title.create', 'job_title.edit', 'job_title.delete',
            'unit.view', 'unit.create', 'unit.edit', 'unit.delete',
            'sub_unit.view', 'sub_unit.create', 'sub_unit.edit', 'sub_unit.delete',
            'cluster.view', 'cluster.create', 'cluster.edit', 'cluster.delete',
        ];

        $permIds = Permission::whereIn('name', $permNames)->pluck('id')->toArray();
        DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        Permission::whereIn('name', $permNames)->delete();
    }
};
