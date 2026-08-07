<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';

    // Roles that are considered "Leader" and can submit assignments
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
     * Staff members who worked on this assignment.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'assignment_user', 'assignment_id', 'user_id');
    }

    /**
     * The leader who submitted this assignment.
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

    /**
     * Dynamically resolve airline name or code from flight identifier.
     */
    public function getAirlineAttribute()
    {
        $flight = $this->ex_flight ?: $this->to_flight;
        if (empty($flight) || $flight === '-') {
            return '-';
        }

        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $flight));
        $iata2 = substr($code, 0, 2);
        $icao3 = substr($code, 0, 3);

        $map = [
            'QF' => 'Qantas',
            'JT' => 'Lion Air',
            'LNI' => 'Lion Air',
            'GA' => 'Garuda',
            'GIA' => 'Garuda',
            'QG' => 'Citilink',
            'CTV' => 'Citilink',
            'ID' => 'Batik Air',
            'BTK' => 'Batik Air',
            'SQ' => 'Singapore Airlines',
            'SIA' => 'Singapore Airlines',
            'MH' => 'Malaysia Airlines',
            'MAS' => 'Malaysia Airlines',
            'AK' => 'AirAsia',
            'FD' => 'AirAsia',
            'QZ' => 'AirAsia',
            'AXM' => 'AirAsia',
            'TR' => 'Scoot',
            'TGW' => 'Scoot',
            'OD' => 'Batik Air Malaysia',
            'MXD' => 'Batik Air Malaysia',
            'SL' => 'Thai Lion Air',
            'TLM' => 'Thai Lion Air',
            'CZ' => 'China Southern',
            'CSN' => 'China Southern',
            'MF' => 'Xiamen Air',
            'CXA' => 'Xiamen Air',
            'SJ' => 'Sriwijaya',
            'SRY' => 'Sriwijaya',
            'IN' => 'Nam Air',
            'LKE' => 'Nam Air',
            '8B' => 'TransNusa',
            'TNU' => 'TransNusa',
        ];

        if (isset($map[$icao3])) {
            return $map[$icao3];
        }
        if (isset($map[$iata2])) {
            return $map[$iata2];
        }
        return $iata2;
    }
}
