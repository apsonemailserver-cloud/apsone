<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'fullname',
        'station_id',
        'no_pas',
        'phone',
        'gender',
        'job_title_id',
        'tim_number',
        'tim_registered',
        'tim_expired',
        'join_date',
        'contract_start',
        'contract_end',
        'pas_registered',
        'pas_expired',
        'salary',
        'is_qantas',
        'unit_id',
        'sub_unit_id',
        'tanggal_lahir',
        'manager',
        'senior_manager',
        'status',
        'alamat',
        'pendidikan',
        'domisili',
        'kota_domisili',
        'no_hp',
        'bpjs_tk',
        'bpjs_kesehatan',
        'no_kk',
        'no_nik',
        'tempat_lahir',
        'cluster_id',
    ];

    public static function generateEmployeeId($date = null): int
    {
        $date = $date ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::now();
        $prefix = $date->format('ym');
        $start = (int) ($prefix . '001');
        $end = (int) ($prefix . '999');

        $maxId = static::whereBetween('id', [$start, $end])->max('id');

        if ($maxId) {
            return (int) $maxId + 1;
        }

        return $start;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->id)) {
                $employee->id = static::generateEmployeeId($employee->created_at ?? null);
            }
        });
    }

    protected $casts = [
        'join_date' => 'date',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'pas_registered' => 'date',
        'pas_expired' => 'date',
        'tim_registered' => 'date',
        'tim_expired' => 'date',
        'tanggal_lahir' => 'date',
        'is_qantas' => 'boolean',
    ];

    public function setStationAttribute($value)
    {
        $this->attributes['station_id'] = $value;
    }

    public function getStationAttribute()
    {
        return $this->attributes['station_id'] ?? null;
    }

    public function getStationIdAttribute()
    {
        return $this->attributes['station_id'] ?? null;
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function subUnit()
    {
        return $this->belongsTo(SubUnit::class, 'sub_unit_id');
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class, 'cluster_id');
    }

    public function stationRelation()
    {
        return $this->belongsTo(Station::class, 'station_id', 'code');
    }
}
