<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCreateScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_create_schedule_handles_large_string_user_id_without_integer_overflow(): void
    {
        $admin = User::create([
            'id' => 'ADMIN01',
            'fullname' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'station' => 'CGK',
            'gender' => 'Male',
            'join_date' => now()->toDateString(),
        ]);

        // Create user with 11-digit NIP (larger than max signed INT)
        User::create([
            'id' => '25100403100',
            'fullname' => 'Porter Large NIP',
            'email' => 'porter@test.com',
            'password' => bcrypt('password'),
            'role' => 'Porter Bge',
            'station' => 'CGK',
            'gender' => 'Male',
            'is_qantas' => false,
            'join_date' => now()->toDateString(),
        ]);

        // Shifts required by ScheduleController::autoCreate
        Shift::create([
            'id' => 'off',
            'name' => 'OFF',
            'description' => 'Libur',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'use_manpower' => 0,
        ]);

        Shift::create([
            'id' => 'P1',
            'name' => 'Pagi 1',
            'description' => 'Shift Pagi 1',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
            'use_manpower' => 6,
        ]);

        $response = $this->actingAs($admin)->post(route('schedule.autoCreate'));

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'user_id' => '25100403100',
        ]);
    }
}
