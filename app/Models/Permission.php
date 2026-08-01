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
     * Map of system modules with readable labels
     */
    public static function modules(): array
    {
        return [
            'dashboard' => 'Dashboard Utama',
            'profile' => 'Profile Karyawan',
            'assignment' => 'Assignment (Work Order)',
            'attendance' => 'Presensi & Absensi',
            'overtime' => 'Lembur',
            'schedule' => 'Jadwal Kerja',
            'shift' => 'Shift Kerja',
            'station' => 'Manajemen Station',
            'user' => 'User Management',
            'blacklist' => 'Blacklist Staff',
            'document' => 'Dokumen',
            'training' => 'Training & Sertifikat',
            'leave' => 'Cuti & Izin',
            'announcement' => 'Pengumuman',
            'role' => 'Hak Akses & Role',
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
            'assignment'   => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'attendance'   => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'overtime'     => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'schedule'     => ['view', 'create', 'edit', 'delete', 'export'],
            'shift'        => ['view', 'create', 'edit', 'delete'],
            'station'      => ['view', 'create', 'edit', 'delete'],
            'user'         => ['view', 'create', 'edit', 'delete', 'export'],
            'blacklist'    => ['view', 'create', 'delete'],
            'role'         => ['view', 'create', 'edit', 'delete'],
            'document'     => ['view', 'create', 'edit', 'delete', 'export'],
            'training'     => ['view', 'create', 'edit', 'delete'],
            'leave'        => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'announcement' => ['view', 'create', 'edit', 'delete'],
        ];
    }

    /**
     * Map of available actions with labels
     */
    public static function actions(): array
    {
        return [
            'view' => 'Lihat / Akses',
            'create' => 'Tambah / Buat',
            'edit' => 'Edit / Ubah',
            'delete' => 'Hapus',
            'approve' => 'Persetujuan / Approval',
            'export' => 'Export / Cetak PDF',
        ];
    }
}
