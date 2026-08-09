<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $table = 'leave_types';

    protected $fillable = [
        'name',
        'default_quota',
        'gender_restriction',
        'is_unlimited',
        'is_active',
    ];

    public function rules()
    {
        return $this->hasMany(LeaveRule::class, 'leave_type_id');
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class, 'leave_type_id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }
}
