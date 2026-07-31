<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkResult extends Model
{
    use HasFactory;

    protected $table = 'work_results';

    // Roles that are considered "Leader" and can submit work results
    public const LEADER_ROLES = [
        'Admin',
        'Leader Aircraft Interior Exterior Cleaning',
        'Leader Bge',
        'Leader Apron',
        'Ass Leader Bge',
        'Ass Leader Apron',
        'Leader Porter Apron',
        'SPV Bge',
        'SPV Apron',
        'Head Of Airport Service',
    ];

    protected $fillable = [
        'date',
        'station',
        'aircraft_reg',
        'ex_flight',
        'to_flight',
        'parking_stand',
        'wo_number',
        'start_time',
        'end_time',
        'photo_path',
        'type',
        'submitted_by',
    ];

    /**
     * Staff members who worked on this WO.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'work_result_user', 'work_result_id', 'user_id');
    }

    /**
     * The leader who submitted this work order.
     */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Duration in minutes between start_time and end_time.
     */
    public function getDurationMinutesAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) return 0;
        try {
            $startStr = strlen($this->start_time) === 5 ? $this->start_time . ':00' : $this->start_time;
            $endStr   = strlen($this->end_time) === 5 ? $this->end_time . ':00' : $this->end_time;
            $start = \Carbon\Carbon::parse($startStr);
            $end   = \Carbon\Carbon::parse($endStr);
            return max(0, $start->diffInMinutes($end));
        } catch (\Exception $e) {
            return 0;
        }
    }
}
