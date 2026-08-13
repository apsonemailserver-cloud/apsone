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
        'job_title',
        'job_title_id',
        'role',
        'role_id',
        'station',
        'station_id',
        'cluster',
        'cluster_id',
        'unit',
        'unit_id',
        'sub_unit',
        'sub_unit_id',
        'status',
        'pic_id',
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
        'face_registered_at',
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

        $roleName = $this->getRoleName();

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

    public function unitRelation()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function subUnitRelation()
    {
        return $this->belongsTo(SubUnit::class, 'sub_unit_id');
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

    public function getNameAttribute()
    {
        return $this->fullname ?? $this->id;
    }

    public static function getStationColumn(): string
    {
        static $col = null;
        if ($col === null) {
            $col = \Illuminate\Support\Facades\Schema::hasColumn('users', 'station_id') ? 'station_id' : 'station';
        }
        return $col;
    }

    public function getStationAttribute()
    {
        $col = static::getStationColumn();
        return $this->attributes[$col] ?? $this->attributes['station'] ?? $this->attributes['station_id'] ?? null;
    }

    public function setStationAttribute($value)
    {
        $col = static::getStationColumn();
        $this->attributes[$col] = $value;
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'station')) {
            $this->attributes['station'] = $value;
        }
    }

    public function stationRelation()
    {
        $foreignKey = static::getStationColumn();
        return $this->belongsTo(Station::class, $foreignKey, 'code');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class, 'cluster_id');
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

    public function setJobTitleAttribute($value)
    {
        if (!empty($value) && \Illuminate\Support\Facades\Schema::hasTable('job_titles')) {
            try {
                $jt = \App\Models\JobTitle::firstOrCreate(['name' => trim($value)]);
                $this->attributes['job_title_id'] = $jt->id;
            } catch (\Throwable $e) {}
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'job_title')) {
            $this->attributes['job_title'] = $value;
        }
    }

    public function setUnitAttribute($value)
    {
        if (!empty($value) && \Illuminate\Support\Facades\Schema::hasTable('units')) {
            try {
                $u = \App\Models\Unit::firstOrCreate(['name' => trim($value)]);
                $this->attributes['unit_id'] = $u->id;
            } catch (\Throwable $e) {}
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'unit')) {
            $this->attributes['unit'] = $value;
        }
    }

    public function setSubUnitAttribute($value)
    {
        if (!empty($value) && \Illuminate\Support\Facades\Schema::hasTable('sub_units')) {
            try {
                $su = \App\Models\SubUnit::firstOrCreate(['name' => trim($value)]);
                $this->attributes['sub_unit_id'] = $su->id;
            } catch (\Throwable $e) {}
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'sub_unit')) {
            $this->attributes['sub_unit'] = $value;
        }
    }

    public function setClusterAttribute($value)
    {
        if (!empty($value) && \Illuminate\Support\Facades\Schema::hasTable('clusters')) {
            try {
                $c = \App\Models\Cluster::firstOrCreate(['name' => trim($value)]);
                $this->attributes['cluster_id'] = $c->id;
            } catch (\Throwable $e) {}
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'cluster')) {
            $this->attributes['cluster'] = $value;
        }
    }
}
