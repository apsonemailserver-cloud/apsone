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
        'role',
        'station',
        'cluster',
        'unit',
        'sub_unit',
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

    public function hasRole($roles): bool
    {
        if (empty($this->role)) {
            return false;
        }

        $userRoles = array_map('trim', explode(',', $this->role));
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

    // Helper Function
    public function isAdmin()
    {
        return $this->role === 'Admin';
    }
    // Relasi ke Lembur
    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    // Relasi ke Work Orders
    public function workOrders()
    {
        return $this->belongsToMany(WorkOrder::class, 'work_order_user', 'user_id', 'work_order_id');
    }

    public function workResults()
    {
        return $this->workOrders();
    }
}
