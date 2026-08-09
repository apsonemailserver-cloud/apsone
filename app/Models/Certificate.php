<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Certificate extends Model
{
    use HasFactory;

    public const TYPES = [
        'Aviation Basics',
        'Basic Safety & Safety Management System (SMS)',
        'Human Factors',
        'Airside Safety',
        'Emergency Response Basic Awareness',
        'Duty Security Training',
        'Dangerous Goods Function 7.4.2',
        'Dangerous Goods Function 7.4.1',
        'Dangerous Goods Function 7.10',
        'Aircraft Ground Operation & Baggage handling Make up area',
        'Security Guard (Basic)',
        'Security Screener (Junior)',
        'Basic Cargo for Porter',
        'ULD Handling & Loading Training For Porter',
        'Baggage Handling & Loading for Porter',
        'Operator Forklift (FLT)',
        'Aircraft Cleaning & Electrical Wiring Harness interconnect system awareness',
        'Lainnya',
    ];

    protected $fillable = [
        'id',
        'user_id',
        'certificate_name',
        'certificate_type',
        'start_date',
        'end_date',
        'certificate_file',
        'status',
        'submitted_by',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    const EXPIRING_SOON_THRESHOLD_DAYS = 30;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return !$this->is_expired && $this->remaining_days <= self::EXPIRING_SOON_THRESHOLD_DAYS;
    }

    public function getRemainingDaysAttribute(): int
    {
        if ($this->end_date->isPast()) {
            return 0;
        }
        return $this->end_date->diffInDays(now());
    }

    public function getStatusAttribute(): string
    {
        if ($this->is_expired) {
            return 'Kadaluarsa';
        } elseif ($this->is_expiring_soon) {
            return 'Akan Kadaluarsa';
        } else {
            return 'Aktif';
        }
    }
}
