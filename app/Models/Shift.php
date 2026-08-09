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
}
