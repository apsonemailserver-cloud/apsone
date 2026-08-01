<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $table = 'stations'; // Nama tabel di database

    protected $fillable = [
        'code',      // Kode (SUB, CGK)
        'name',      // Nama (Surabaya, Jakarta)
        'is_active', // Status (1/0)
        'latitude',  // Latitude
        'longitude', // Longitude
        'radius',    // Radius absensi (meter)
        'role',      // Role-role di station
    ];

    public function isRoleAllowed(User $user): bool
    {
        if (empty($this->role)) {
            return true;
        }

        $allowedRoles = array_filter(array_map('trim', explode(',', (string) $this->role)));

        if (empty($allowedRoles)) {
            return true;
        }

        $userRoles = array_filter(array_map('trim', explode(',', (string) $user->role)));

        return count(array_intersect($userRoles, $allowedRoles)) > 0;
    }
}
