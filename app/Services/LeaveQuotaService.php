<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRule;
use App\Models\LeaveBalance;
use App\Models\Leave;
use Carbon\Carbon;

class LeaveQuotaService
{
    /**
     * Calculate correct quota for a user and leave type in a specific year.
     */
    public function calculateQuota(User $user, LeaveType $leaveType, int $year): int
    {
        // 1. If it's unlimited, return 0
        if ($leaveType->is_unlimited) {
            return 0;
        }

        // 2. Check if user has join_date. If not, default to leave type's default quota
        if (!$user->join_date) {
            return $leaveType->default_quota;
        }

        // 3. Calculate tenure in years
        $joinDate = Carbon::parse($user->join_date);
        $targetDate = Carbon::create($year, 1, 1)->isCurrentYear() 
            ? Carbon::now() 
            : Carbon::create($year, 12, 31);
        
        $tenureYears = max(0, $joinDate->diffInYears($targetDate));

        // 4. Find matching rule
        $rule = LeaveRule::where('leave_type_id', $leaveType->id)
            ->where('min_tenure_years', '<=', $tenureYears)
            ->where(function ($q) use ($tenureYears) {
                $q->whereNull('max_tenure_years')
                  ->orWhere('max_tenure_years', '>=', $tenureYears);
            })
            ->first();

        if ($rule) {
            return $rule->quota_days;
        }

        return $leaveType->default_quota;
    }

    /**
     * Synchronize leave balances for a specific user and year.
     */
    public function syncBalancesForUser(User $user, ?int $year = null): void
    {
        $year ??= (int) date('Y');
        
        // Get active leave types
        $leaveTypes = LeaveType::where('is_active', true)->get();

        foreach ($leaveTypes as $leaveType) {
            // Calculate total quota based on rules
            $totalQuota = $this->calculateQuota($user, $leaveType, $year);

            // Re-calculate used days
            $usedDays = (int) Leave::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->where(function ($q) use ($leaveType) {
                    $q->where('leave_type_id', $leaveType->id)
                      ->orWhere(function ($q2) use ($leaveType) {
                          $q2->whereNull('leave_type_id')
                             ->where('leave_type', $leaveType->name);
                      });
                })
                ->sum('total_days');

            // Re-calculate pending days
            $pendingDays = (int) Leave::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'pending Apron', 'pending Bge'])
                ->whereYear('start_date', $year)
                ->where(function ($q) use ($leaveType) {
                    $q->where('leave_type_id', $leaveType->id)
                      ->orWhere(function ($q2) use ($leaveType) {
                          $q2->whereNull('leave_type_id')
                             ->where('leave_type', $leaveType->name);
                      });
                })
                ->sum('total_days');

            $remainingDays = $leaveType->is_unlimited 
                ? 999 
                : max(0, $totalQuota - $usedDays);

            // Create or update balance
            LeaveBalance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                ],
                [
                    'total_quota' => $totalQuota,
                    'used_days' => $usedDays,
                    'pending_days' => $pendingDays,
                    'remaining_days' => $remainingDays,
                ]
            );
        }
    }

    /**
     * Synchronize balances for all active users in a specific year.
     */
    public function syncAllBalances(?int $year = null): void
    {
        $year ??= (int) date('Y');
        $users = User::where('is_active', true)->get();

        foreach ($users as $user) {
            $this->syncBalancesForUser($user, $year);
        }
    }

    /**
     * Validate leave eligibility. Returns array with keys ['eligible' => bool, 'message' => ?string]
     */
    public function verifyEligibility(User $user, LeaveType $leaveType, int $requestedDays, Carbon $startDate, Carbon $endDate, ?int $excludeLeaveId = null): array
    {
        $year = (int) $startDate->year;

        // 1. Gender Restriction Check
        if ($leaveType->gender_restriction !== 'All') {
            $userGender = strtoupper($user->gender ?? '');
            
            $isMaleUser = in_array($userGender, ['MALE', 'L', 'LAKI-LAKI', 'LAKI_LAKI']);
            $isFemaleUser = in_array($userGender, ['FEMALE', 'P', 'PEREMPUAN']);

            if ($leaveType->gender_restriction === 'Male' && !$isMaleUser) {
                return [
                    'eligible' => false,
                    'message' => "Pengajuan ditolak. Jenis cuti ini ({$leaveType->name}) hanya diperuntukkan bagi karyawan pria.",
                ];
            }

            if ($leaveType->gender_restriction === 'Female' && !$isFemaleUser) {
                return [
                    'eligible' => false,
                    'message' => "Pengajuan ditolak. Jenis cuti ini ({$leaveType->name}) hanya diperuntukkan bagi karyawan wanita.",
                ];
            }
        }

        // 2. Quota Availability Check
        if (!$leaveType->is_unlimited) {
            // Ensure balance exists
            $balance = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                $this->syncBalancesForUser($user, $year);
                $balance = LeaveBalance::where('user_id', $user->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $year)
                    ->first();
            }

            // Exclude current leave if updating
            $usedDays = (int) Leave::where('user_id', $user->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->when($excludeLeaveId, fn($q) => $q->where('id', '!=', $excludeLeaveId))
                ->sum('total_days');

            $projectedRemaining = ($balance ? $balance->total_quota : $leaveType->default_quota) - $usedDays;

            if ($projectedRemaining < $requestedDays) {
                return [
                    'eligible' => false,
                    'message' => "Pengajuan ditolak. Saldo jatah cuti ({$leaveType->name}) Anda tidak mencukupi. Sisa saldo saat ini: {$projectedRemaining} hari, diajukan: {$requestedDays} hari.",
                ];
            }
        }

        return [
            'eligible' => true,
            'message' => null,
        ];
    }
}
