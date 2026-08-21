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
        'employee_id',
        'role_id',
        'role',
        'email',
        'password',
        'profile_picture',
        'face_registered_at',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeWithEmployee($query)
    {
        return $query->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
            ->select('users.*');
    }

    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getRoleAttribute()
    {
        return $this->roleRelation ? $this->roleRelation->name : ($this->attributes['role'] ?? null);
    }

    public function hasRole($roles): bool
    {
        $roleName = $this->getRoleName();
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Magic getter for backward compatibility (accessing employee attributes directly on user).
     */
    public function __get($key)
    {
        $value = parent::__get($key);
        if ($value !== null) {
            return $value;
        }

        if (in_array($key, [
            'fullname', 'station', 'station_id', 'no_pas', 'phone', 'gender', 'job_title_id',
            'tim_number', 'tim_registered', 'tim_expired', 'join_date',
            'contract_start', 'contract_end', 'pas_registered', 'pas_expired',
            'salary', 'is_qantas', 'unit_id', 'sub_unit_id', 'tanggal_lahir',
            'manager', 'senior_manager', 'status', 'alamat', 'pendidikan',
            'domisili', 'kota_domisili', 'no_hp', 'bpjs_tk', 'bpjs_kesehatan',
            'no_kk', 'no_nik', 'tempat_lahir', 'cluster_id'
        ])) {
            return $this->employee ? $this->employee->{$key} : null;
        }

        return null;
    }

    public function getNameAttribute()
    {
        return $this->employee->fullname ?? $this->fullname ?? $this->id;
    }

    public function getFullnameAttribute()
    {
        return $this->employee ? $this->employee->fullname : null;
    }

    public function jobTitle()
    {
        return $this->employee ? $this->employee->jobTitle() : $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function unit()
    {
        return $this->employee ? $this->employee->unit() : $this->belongsTo(Unit::class, 'unit_id');
    }

    public function subUnit()
    {
        return $this->employee ? $this->employee->subUnit() : $this->belongsTo(SubUnit::class, 'sub_unit_id');
    }

    public function cluster()
    {
        return $this->employee ? $this->employee->cluster() : $this->belongsTo(Cluster::class, 'cluster_id');
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

    public function getRoleName(): ?string
    {
        if (!empty($this->attributes['role'])) {
            return $this->attributes['role'];
        }
        if ($this->roleRelation) {
            return $this->roleRelation->name;
        }
        return null;
    }

    public function isAdmin()
    {
        $roleName = $this->getRoleName();
        return $roleName === 'Admin';
    }

    public function hasPermission(string $permissionName): bool
    {
        if (isset($this->is_active) && !$this->is_active) {
            return false;
        }

        if ($permissionName === 'profile.view' || $permissionName === 'dashboard.view') {
            return true;
        }

        try {
            $roleId = $this->role_id ?? ($this->roleRelation ? $this->roleRelation->id : null);
            if ($roleId) {
                return Role::where('id', $roleId)
                    ->whereHas('permissions', function ($query) use ($permissionName) {
                        $query->where('name', $permissionName);
                    })
                    ->exists();
            }

            $roleName = $this->getRoleName();
            if (empty($roleName)) {
                return false;
            }

            $userRoles = array_map('trim', explode(',', $roleName));
            return Role::whereIn('name', $userRoles)
                ->whereHas('permissions', function ($query) use ($permissionName) {
                    $query->where('name', $permissionName);
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
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

    public function unitRelation()
    {
        return $this->employee ? $this->employee->unit() : $this->belongsTo(Unit::class, 'unit_id');
    }

    public function subUnitRelation()
    {
        return $this->employee ? $this->employee->subUnit() : $this->belongsTo(SubUnit::class, 'sub_unit_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'user_id');
    }

    public function leaveTypes()
    {
        return $this->belongsToMany(LeaveType::class, 'leave_balances', 'user_id', 'leave_type_id')
            ->withPivot(['year', 'total_quota', 'used_days', 'pending_days', 'remaining_days'])
            ->withTimestamps();
    }

    public static function getStationColumn(): string
    {
        return 'station_id';
    }

    public function getStationAttribute()
    {
        return $this->employee ? $this->employee->station_id : null;
    }

    public function getStationIdAttribute()
    {
        return $this->employee ? $this->employee->station_id : null;
    }

    public function stationRelation()
    {
        return $this->employee ? $this->employee->stationRelation() : null;
    }

    public function setRoleAttribute($value)
    {
        if (!empty($value) && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
            try {
                $r = \App\Models\Role::firstOrCreate(['name' => trim($value)]);
                $this->attributes['role_id'] = $r->id;
            } catch (\Throwable $e) {}
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $this->attributes['role'] = $value;
        }
    }
}
