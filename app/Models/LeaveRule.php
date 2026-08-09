<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRule extends Model
{
    use HasFactory;

    protected $table = 'leave_rules';

    protected $fillable = [
        'leave_type_id',
        'min_tenure_years',
        'max_tenure_years',
        'quota_days',
        'description',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
