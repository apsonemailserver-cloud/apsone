<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'attendance_id',
        'station_id',
        'attendance_date',
        'proposed_check_in_time',
        'proposed_check_out_time',
        'reason',
        'rejection_reason',
        'status',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date:Y-m-d',
            'proposed_check_in_time' => 'datetime',
            'proposed_check_out_time' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station', 'code');
    }

    public function getStationIdAttribute()
    {
        return $this->attributes['station'] ?? null;
    }

    public function setStationIdAttribute($value)
    {
        $this->attributes['station'] = $value;
    }

    public function setStationAttribute($value)
    {
        $this->attributes['station'] = $value;
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
