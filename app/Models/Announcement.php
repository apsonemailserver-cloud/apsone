<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'target_stations',
        'created_by',
    ];

    protected $casts = [
        'target_stations' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class, 'announcement_id');
    }

    public function isReadBy($user): bool
    {
        $userId = is_object($user) ? $user->id : $user;
        return $this->reads()->where('user_id', $userId)->exists();
    }

    public function scopeForUser($query, $user)
    {
        $station = is_object($user) ? $user->station : $user;
        return $query->where(function ($q) use ($station) {
            $q->whereNull('target_stations')
              ->orWhere('target_stations', 'like', '%"ALL"%')
              ->orWhere('target_stations', 'like', '%"all"%')
              ->orWhere('target_stations', '[]')
              ->orWhere('target_stations', '');
            if (!empty($station)) {
                $q->orWhere('target_stations', 'like', '%"' . $station . '"%');
            }
        });
    }
}
