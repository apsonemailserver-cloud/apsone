<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module',
        'action',
        'label',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Map of system modules with readable labels matching sidebar menu
     */
    public static function modules(): array
    {
        return [
            // Menu
            'dashboard'    => 'Dashboard',
            'profile'      => 'Profile',
            'schedule'     => 'Schedule',
            'shift'        => 'Shift',
            'attendance'   => 'Attendance',
            'overtime'     => 'Overtime',
            'assignment'   => 'Assignment',

            // Administrator
            'station'      => 'Station Management',
            'user'         => 'Station Monitoring (Staff)',
            'role'         => 'Role & Permissions',
            'blacklist'    => 'Blacklist Staff',
            'job_title'    => 'Job Titles',
            'unit'         => 'Units',
            'sub_unit'     => 'Sub Units',

            // General
            'document'     => 'Documents',
            'training'     => 'Training',
            'leave'        => 'Apply Leave',
            'master_leave' => 'Master Cuti',
            'announcement' => 'Announcement',
        ];
    }

    /**
     * Map of valid actions for each module
     */
    public static function moduleActionsMap(): array
    {
        return [
            'dashboard'    => ['view'],
            'profile'      => ['view', 'edit'],
            'schedule'     => ['view', 'create', 'edit', 'delete', 'sync', 'export'],
            'shift'        => ['view', 'create', 'edit', 'delete'],
            'attendance'   => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'overtime'     => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'assignment'   => ['view', 'create', 'edit', 'delete', 'approve', 'export'],

            'station'      => ['view', 'create', 'edit', 'delete'],
            'user'         => ['view', 'create', 'edit', 'delete', 'export', 'reset_face'],
            'role'         => ['view', 'create', 'edit', 'delete'],
            'blacklist'    => ['view', 'create', 'delete'],
            'job_title'    => ['view', 'create', 'edit', 'delete'],
            'unit'         => ['view', 'create', 'edit', 'delete'],
            'sub_unit'     => ['view', 'create', 'edit', 'delete'],

            'document'     => ['view', 'create', 'edit', 'delete', 'export'],
            'training'     => ['view', 'create', 'edit', 'delete'],
            'leave'        => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'master_leave' => ['view', 'create', 'edit', 'delete', 'sync'],
            'announcement' => ['view', 'create', 'edit', 'delete'],
        ];
    }

    /**
     * Map of available actions with labels
     */
    public static function actions(): array
    {
        return [
            'view'       => 'Lihat / Akses',
            'create'     => 'Tambah / Buat',
            'edit'       => 'Edit / Ubah',
            'delete'     => 'Hapus',
            'approve'    => 'Persetujuan / Approval',
            'sync'       => 'Sync / Auto',
            'export'     => 'Export / Cetak PDF',
            'reset_face' => 'Reset / Hapus Foto Wajah',
        ];
    }
}
