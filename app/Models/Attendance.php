<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'station',
        'station_id',
        'check_in_time',
        'check_out_time',
        'status',
        'check_in_ip',
        'check_out_ip',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_notes',
        'check_out_notes',
        'check_in_photo',
        'check_out_photo',
    ];

    /**
     * Define relationship with User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function station()
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'station')) {
            return $this->belongsTo(Station::class, 'station', 'code');
        }

        return $this->belongsTo(Station::class, 'station_id');
    }

    public function getStationAttribute()
    {
        if (array_key_exists('station', $this->relations)) {
            return $this->relations['station'];
        }

        $code = $this->attributes['station'] ?? $this->attributes['station_id'] ?? null;
        if (!empty($code)) {
            $station = Station::where('code', $code)->first();
            $this->setRelation('station', $station);
            return $station;
        }

        return null;
    }

    public function getStationIdAttribute()
    {
        return $this->attributes['station_id'] ?? $this->attributes['station'] ?? null;
    }

    public function setStationIdAttribute($value)
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'station_id')) {
            $this->attributes['station_id'] = $value;
        } else {
            $this->attributes['station'] = $value;
        }
    }

    public function setStationAttribute($value)
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'station')) {
            $this->attributes['station'] = $value;
        } else {
            $this->attributes['station_id'] = $value;
        }
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
