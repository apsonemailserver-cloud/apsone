<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shifts';

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'description', 'start_time', 'end_time', 'tolerance_minutes', 'use_manpower'];

    public $incrementing = false;
    protected $keyType = 'string';

    public static function generateNextId(string $shiftName): string
    {
        $prefix = match (strtolower(trim($shiftName))) {
            'pagi' => 'P',
            'siang' => 'S',
            'malam' => 'M',
            default => 'P',
        };

        // Cari ID tertinggi dengan prefix tersebut
        $existingIds = static::where('id', 'LIKE', $prefix . '%')->pluck('id')->toArray();

        $maxNumber = 0;
        foreach ($existingIds as $id) {
            $numPart = preg_replace('/[^0-9]/', '', substr($id, strlen($prefix)));
            if (is_numeric($numPart) && (int)$numPart > $maxNumber) {
                $maxNumber = (int)$numPart;
            }
        }

        $nextNum = $maxNumber + 1;
        $candidate = $prefix . $nextNum;

        while (static::where('id', $candidate)->exists()) {
            $nextNum++;
            $candidate = $prefix . $nextNum;
        }

        return $candidate;
    }
}
