<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Leave;
use App\Models\Certificate; // <--- PASTIKAN BARIS INI ADA!

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */

    use HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // GABUNGKAN SEMUA KOLOM DISINI (JANGAN ADA DUA FILLABLE)
    protected $fillable = [
        'id',
        'fullname',
        'email',
        'password',
        'is_active',
        'gender',
        'job_title_id',
        'role_id',
        'station',
        'cluster_id',
        'unit_id',
        'sub_unit_id',
        'status',
        'manager',
        'senior_manager',
        'is_qantas',
        'join_date',
        'salary',
        'contract_start',
        'contract_end',
        'phone',
        'pendidikan',
        'tanggal_lahir',
        'tempat_lahir',
        'domisili',
        'kota_domisili',
        'no_hp',
        'alamat',
        'no_nik',
        'no_kk',
        'npwp',
        'no_pas',
        'pas_registered',
        'pas_expired',
        'bpjs_kesehatan',
        'bpjs_tk',
        'tim_number',
        'tim_registered',
        'tim_expired',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getRoleAttribute()
    {
        return $this->roleRelation ? $this->roleRelation->name : null;
    }

    public function hasRole($roles): bool
    {
        $roleName = $this->roleRelation ? $this->roleRelation->name : null;
        if (empty($roleName)) {
            return false;
        }

        $userRoles = array_map('trim', explode(',', $roleName));
        if (is_array($roles)) {
            return count(array_intersect($roles, $userRoles)) > 0;
        }

        return in_array($roles, $userRoles);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            // Casting Tanggal (Penting agar tidak error saat format tanggal di View)
            'join_date' => 'date',
            'contract_start' => 'date',
            'contract_end' => 'date',
            'pas_registered' => 'date',
            'pas_expired' => 'date',
            'tim_expired' => 'date', // TIM

            // Boolean
            'is_qantas' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // =================================================================
    // RELASI ANTAR TABEL
    // =================================================================

    // Relasi ke Cuti
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    // Relasi ke Sertifikat Training
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    // Relasi: User ini punya satu atasan (PIC)
    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    // Relasi: User ini punya banyak bawahan (Jika dia Leader)
    public function subordinates()
    {
        return $this->hasMany(User::class, 'pic_id');
    }

    public function isAdmin()
    {
        $roleName = $this->roleRelation ? $this->roleRelation->name : null;
        return $roleName === 'Admin';
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($permissionName === 'profile.view') {
            return true;
        }

        $roleName = $this->roleRelation ? $this->roleRelation->name : null;

        if ($this->isAdmin() || $roleName === 'Admin') {
            return true;
        }

        if (empty($roleName)) {
            return false;
        }

        $userRoles = array_map('trim', explode(',', $roleName));
        if (in_array('Admin', $userRoles)) {
            return true;
        }

        try {
            return Role::whereIn('name', $userRoles)
                ->whereHas('permissions', function ($query) use ($permissionName) {
                    $query->where('name', $permissionName);
                })
                ->exists();
        } catch (\Throwable $e) {
            return true;
        }
    }

    public function canAccess(string $module, string $action = 'view'): bool
    {
        return $this->hasPermission("{$module}.{$action}");
    }
    // Relasi ke Lembur
    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    // Relasi ke Assignments
    public function assignments()
    {
        return $this->belongsToMany(Assignment::class, 'assignment_user', 'user_id', 'assignment_id');
    }

    public function workOrders()
    {
        return $this->assignments();
    }

    public function workResults()
    {
        return $this->assignments();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function subUnit()
    {
        return $this->belongsTo(SubUnit::class, 'sub_unit_id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class, 'cluster_id');
    }
}
