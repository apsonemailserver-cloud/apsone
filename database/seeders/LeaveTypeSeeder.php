<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\LeaveRule;
use App\Models\User;
use App\Services\LeaveQuotaService;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Leave Types
        $annual = LeaveType::updateOrCreate(
            ['name' => 'Cuti Tahunan'],
            [
                'default_quota' => 12,
                'gender_restriction' => 'All',
                'is_unlimited' => false,
            ]
        );

        $maternity = LeaveType::updateOrCreate(
            ['name' => 'Cuti Melahirkan'],
            [
                'default_quota' => 90,
                'gender_restriction' => 'Female',
                'is_unlimited' => false,
            ]
        );

        $paternity = LeaveType::updateOrCreate(
            ['name' => 'Cuti Istri Melahirkan'],
            [
                'default_quota' => 2,
                'gender_restriction' => 'Male',
                'is_unlimited' => false,
            ]
        );

        $sick = LeaveType::updateOrCreate(
            ['name' => 'Cuti Sakit'],
            [
                'default_quota' => 0,
                'gender_restriction' => 'All',
                'is_unlimited' => true,
            ]
        );

        $marriage = LeaveType::updateOrCreate(
            ['name' => 'Cuti Menikah'],
            [
                'default_quota' => 3,
                'gender_restriction' => 'All',
                'is_unlimited' => false,
            ]
        );

        $bereavement = LeaveType::updateOrCreate(
            ['name' => 'Cuti Duka'],
            [
                'default_quota' => 2,
                'gender_restriction' => 'All',
                'is_unlimited' => false,
            ]
        );

        $longService = LeaveType::updateOrCreate(
            ['name' => 'Cuti Besar Masa Kerja'],
            [
                'default_quota' => 0,
                'gender_restriction' => 'All',
                'is_unlimited' => false,
            ]
        );

        // 2. Seed Leave Rules for ANNUAL leave type
        LeaveRule::where('leave_type_id', $annual->id)->delete();
        LeaveRule::create([
            'leave_type_id' => $annual->id,
            'min_tenure_years' => 0,
            'max_tenure_years' => 0,
            'quota_days' => 0,
            'description' => 'Masa kerja < 1 tahun: belum berhak cuti tahunan',
        ]);
        LeaveRule::create([
            'leave_type_id' => $annual->id,
            'min_tenure_years' => 1,
            'max_tenure_years' => 2,
            'quota_days' => 12,
            'description' => 'Masa kerja 1-2 tahun: 12 hari per tahun',
        ]);
        LeaveRule::create([
            'leave_type_id' => $annual->id,
            'min_tenure_years' => 3,
            'max_tenure_years' => 4,
            'quota_days' => 14,
            'description' => 'Masa kerja 3-4 tahun: 14 hari per tahun',
        ]);
        LeaveRule::create([
            'leave_type_id' => $annual->id,
            'min_tenure_years' => 5,
            'max_tenure_years' => 14,
            'quota_days' => 18,
            'description' => 'Masa kerja 5-14 tahun: 18 hari per tahun',
        ]);
        LeaveRule::create([
            'leave_type_id' => $annual->id,
            'min_tenure_years' => 15,
            'max_tenure_years' => null,
            'quota_days' => 24,
            'description' => 'Masa kerja >= 15 tahun: 24 hari per tahun',
        ]);

        // Seed Leave Rules for LONG_SERVICE leave type
        LeaveRule::where('leave_type_id', $longService->id)->delete();
        LeaveRule::create([
            'leave_type_id' => $longService->id,
            'min_tenure_years' => 0,
            'max_tenure_years' => 4,
            'quota_days' => 0,
            'description' => 'Masa kerja < 5 tahun: tidak mendapatkan cuti besar',
        ]);
        LeaveRule::create([
            'leave_type_id' => $longService->id,
            'min_tenure_years' => 5,
            'max_tenure_years' => null,
            'quota_days' => 30,
            'description' => 'Masa kerja >= 5 tahun: jatah cuti besar 30 hari',
        ]);

        // 3. Auto-populate initial balances for current active users
        $service = new LeaveQuotaService();
        $service->syncAllBalances(date('Y'));
    }
}
