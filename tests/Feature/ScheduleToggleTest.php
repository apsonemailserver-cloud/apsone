<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_roles_can_toggle_schedule_active_status()
    {
        $shift = Shift::create([
            'id'             => 'P1',
            'name'           => 'Shift P1',
            'description'    => 'Shift Siang',
            'start_time'     => '07:00:00',
            'end_time'       => '15:00:00',
            'use_manpower'   => 5,
            'require_qantas' => 1,
        ]);

        $staff = User::factory()->create(['role' => 'staff']);

        $schedule = Schedule::create([
            'user_id'   => $staff->id,
            'shift_id'  => $shift->id,
            'date'      => now()->toDateString(),
            'is_active' => 1,
        ]);

        $leaderApron = User::factory()->create(['role' => 'Leader Apron']);

        // Leader Apron can toggle schedule active status to 0
        $response = $this->actingAs($leaderApron)->post(route('schedule.updateActive'), [
            'id'        => $schedule->id,
            'is_active' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Status aktif staff diperbarui']);
        $this->assertEquals(0, $schedule->fresh()->is_active);
    }

    public function test_unauthorized_staff_cannot_toggle_schedule_active_status()
    {
        $shift = Shift::create([
            'id'             => 'P2',
            'name'           => 'Shift P2',
            'description'    => 'Shift Siang',
            'start_time'     => '07:00:00',
            'end_time'       => '15:00:00',
            'use_manpower'   => 5,
            'require_qantas' => 1,
        ]);

        $staff1 = User::factory()->create(['role' => 'staff']);
        $staff2 = User::factory()->create(['role' => 'staff']);

        $schedule = Schedule::create([
            'user_id'   => $staff1->id,
            'shift_id'  => $shift->id,
            'date'      => now()->toDateString(),
            'is_active' => 1,
        ]);

        $response = $this->actingAs($staff2)->post(route('schedule.updateActive'), [
            'id'        => $schedule->id,
            'is_active' => 0,
        ]);

        $response->assertStatus(403);
    }
}
