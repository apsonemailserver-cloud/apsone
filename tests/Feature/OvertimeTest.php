<?php

namespace Tests\Feature;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_reject_overtime_with_reason(): void
    {
        $leader = User::create([
            'id' => '102001',
            'fullname' => 'Leader Apron',
            'email' => 'leader@example.com',
            'password' => 'password',
            'role' => 'LEADER',
            'station' => 'CGK',
            'is_active' => true,
            'gender' => 'Male',
            'join_date' => '2026-01-01',
            'salary' => '0',
        ]);

        $staff = User::create([
            'id' => '102002',
            'fullname' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'Staff',
            'station' => 'CGK',
            'is_active' => true,
            'gender' => 'Male',
            'join_date' => '2026-01-01',
            'salary' => '0',
        ]);

        $overtime = Overtime::create([
            'user_id' => $staff->id,
            'date' => '2026-07-28',
            'duration' => 2,
            'title' => 'Deep Cleaning',
            'description' => 'Clean aircraft',
            'status' => 'Pending',
        ]);

        $this->actingAs($leader)
            ->post(route('overtime.reject', $overtime->id), [
                'rejection_reason' => 'Keterangan pekerjaan tidak lengkap',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'status' => 'Rejected',
            'rejection_reason' => 'Keterangan pekerjaan tidak lengkap',
            'approved_by' => 'Leader Apron',
        ]);
    }
}
