<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-26 12:00:00');
        Schema::dropAllTables();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_operational_staff_receives_personal_dashboard_with_scoped_data(): void
    {
        [$staff, $otherStaff] = $this->seedDashboardData();

        $response = $this->actingAs($staff)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Pengerjaan 1 Bulan Terakhir')
            ->assertSee('Persentase Kehadiran Anda (1 Bulan Terakhir)')
            ->assertSee('Penerbangan Selesai (1 Bulan Terakhir)')
            ->assertSee('Riwayat Presensi Anda')
            ->assertSee('(7 Hari Terakhir)')
            ->assertSee('Data Pengerjaan')
            ->assertSee('(1 Bulan Terakhir)')
            ->assertSee('Data Penerbangan')
            ->assertSee('<div class="stat-value">4</div>', false)
            ->assertSee('<div class="stat-value" data-animate-counter="false">75.00%</div>', false)
            ->assertSee('GA100')
            ->assertSee('GA102')
            ->assertSee('GA105')
            ->assertSee('GA106')
            ->assertSee('GA120')
            ->assertDontSee('GA107')
            ->assertDontSee('GA131')
            ->assertDontSee('JT900')
            ->assertDontSee('Total Staff GLOBAL')
            ->assertDontSee('Distribusi Staff by Role')
            ->assertDontSee('const lineChartLabels');

        $this->assertFalse($response->viewData('showManagementDashboard'));
        $this->assertSame(4, $response->viewData('personalAssignmentsLastMonth'));
        $this->assertSame(2, $response->viewData('personalCompletedFlightsLastMonth'));
        $this->assertSame(75.0, $response->viewData('personalAttendancePercentage'));
        $this->assertEqualsCanonicalizing(
            ['GA100', 'GA101', 'GA105', 'GA120'],
            $response->viewData('assignedFlights')->pluck('flight_number')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['GA100', 'GA101', 'GA102', 'GA105', 'GA106'],
            $response->viewData('flights')->pluck('flight_number')->all()
        );
        $this->assertNotContains(
            'GA107',
            $response->viewData('flights')->pluck('flight_number')->all()
        );
        $this->assertNotContains(
            'JT900',
            $response->viewData('flights')->pluck('flight_number')->all()
        );
        $this->assertEquals(
            ['2026-07-26', '2026-07-21'],
            $response->viewData('personalAttendanceHistory')
                ->pluck('check_in_time')
                ->map(fn ($checkIn) => Carbon::parse($checkIn)->toDateString())
                ->all()
        );
        $this->assertTrue(
            $response->viewData('personalAttendanceHistory')
                ->every(fn ($attendance) => $attendance->user_id === $staff->id)
        );
        $this->assertFalse(
            $response->viewData('personalAttendanceHistory')
                ->contains('user_id', $otherStaff->id)
        );
    }

    public function test_multi_role_staff_with_management_role_keeps_management_dashboard(): void
    {
        [$staff] = $this->seedDashboardData();
        $staff->update(['role' => 'Porter Bge, Leader Bge']);

        $response = $this->actingAs($staff)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Total Staff GLOBAL')
            ->assertSee('Distribusi Staff by Role')
            ->assertSee('const lineChartLabels')
            ->assertDontSee('Riwayat Presensi Anda');

        $this->assertTrue($response->viewData('showManagementDashboard'));
    }

    private function seedDashboardData(): array
    {
        DB::table('stations')->insert([
            [
                'id' => 1,
                'code' => 'CGK',
                'name' => 'Jakarta',
                'is_active' => true,
                'latitude' => -6.2354184,
                'longitude' => 106.7780129,
                'radius' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'SUB',
                'name' => 'Surabaya',
                'is_active' => true,
                'latitude' => -7.379831,
                'longitude' => 112.786819,
                'radius' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $staff = $this->makeUser('2607001', 'Staff CGK', 'Porter Bge', 'CGK');
        $otherStaff = $this->makeUser('2607002', 'Staff SUB Rahasia', 'Porter Apron', 'SUB');

        DB::table('shifts')->insert([
            'id' => 'P',
            'name' => 'Pagi',
            'description' => 'Shift pagi',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'use_manpower' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('schedules')->insert([
            ['id' => 1, 'user_id' => $staff->id, 'date' => '2026-07-01', 'shift_id' => 'P', 'is_active' => true],
            ['id' => 2, 'user_id' => $staff->id, 'date' => '2026-07-02', 'shift_id' => 'P', 'is_active' => true],
            ['id' => 3, 'user_id' => $staff->id, 'date' => '2026-07-26', 'shift_id' => 'P', 'is_active' => true],
            ['id' => 4, 'user_id' => $otherStaff->id, 'date' => '2026-07-26', 'shift_id' => 'P', 'is_active' => true],
            ['id' => 5, 'user_id' => $staff->id, 'date' => '2026-07-21', 'shift_id' => 'P', 'is_active' => true],
            ['id' => 6, 'user_id' => $staff->id, 'date' => '2026-06-26', 'shift_id' => 'P', 'is_active' => true],
        ]);

        DB::table('attendances')->insert([
            [
                'id' => 1,
                'user_id' => $staff->id,
                'station_id' => 1,
                'check_in_time' => '2026-07-01 08:00:00',
                'check_out_time' => '2026-07-01 17:00:00',
                'created_at' => '2026-07-01 08:00:00',
                'updated_at' => '2026-07-01 17:00:00',
            ],
            [
                'id' => 2,
                'user_id' => $staff->id,
                'station_id' => 1,
                'check_in_time' => '2026-07-26 08:00:00',
                'check_out_time' => null,
                'created_at' => '2026-07-26 08:00:00',
                'updated_at' => '2026-07-26 08:00:00',
            ],
            [
                'id' => 3,
                'user_id' => $otherStaff->id,
                'station_id' => 2,
                'check_in_time' => '2026-07-26 07:00:00',
                'check_out_time' => '2026-07-26 16:00:00',
                'created_at' => '2026-07-26 07:00:00',
                'updated_at' => '2026-07-26 16:00:00',
            ],
            [
                'id' => 4,
                'user_id' => $staff->id,
                'station_id' => 1,
                'check_in_time' => '2026-07-21 08:00:00',
                'check_out_time' => '2026-07-21 17:00:00',
                'created_at' => '2026-07-21 08:00:00',
                'updated_at' => '2026-07-21 17:00:00',
            ],
            [
                'id' => 5,
                'user_id' => $staff->id,
                'station_id' => 1,
                'check_in_time' => '2026-07-19 08:00:00',
                'check_out_time' => '2026-07-19 17:00:00',
                'created_at' => '2026-07-19 08:00:00',
                'updated_at' => '2026-07-19 17:00:00',
            ],
        ]);

        DB::table('flights')->insert([
            $this->flightRow(1, 'GA100', 'CGK', false),
            $this->flightRow(2, 'GA101', 'CGK', true),
            $this->flightRow(3, 'GA102', 'CGK', false),
            $this->flightRow(4, 'JT900', 'SUB', true),
            $this->flightRow(5, 'GA105', 'CGK', true, '2026-07-21 09:00:00'),
            $this->flightRow(6, 'GA120', 'CGK', false, '2026-07-06 09:00:00'),
            $this->flightRow(7, 'GA131', 'CGK', true, '2026-06-25 09:00:00'),
            $this->flightRow(8, 'GA107', 'CGK', false, '2026-07-19 09:00:00'),
            $this->flightRow(9, 'GA106', 'CGK', false, '2026-07-20 09:00:00'),
        ]);

        DB::table('flight_details')->insert([
            ['flight_id' => 1, 'schedule_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['flight_id' => 2, 'schedule_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['flight_id' => 4, 'schedule_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['flight_id' => 5, 'schedule_id' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['flight_id' => 6, 'schedule_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['flight_id' => 7, 'schedule_id' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);

        return [$staff, $otherStaff];
    }

    private function flightRow(
        int $id,
        string $number,
        string $station,
        bool $status,
        string $createdAt = '2026-07-26 09:00:00'
    ): array {
        return [
            'id' => $id,
            'airline' => str_starts_with($number, 'GA') ? 'Garuda Indonesia' : 'Lion Air',
            'flight_number' => $number,
            'registasi' => 'PK-'.$number,
            'type' => 'Narrowbody',
            'arrival' => '10:00:00',
            'time_count' => '11:00:00',
            'station' => $station,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    private function makeUser(string $id, string $name, string $role, string $station): User
    {
        return User::create([
            'id' => $id,
            'fullname' => $name,
            'email' => "{$id}@example.com",
            'password' => 'password',
            'is_active' => true,
            'gender' => 'Male',
            'role' => $role,
            'station' => $station,
            'manager' => null,
            'is_qantas' => false,
            'join_date' => '2026-01-01',
            'salary' => '0',
            'pas_expired' => '2027-12-31',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('gender');
            $table->string('role');
            $table->string('station');
            $table->string('manager')->nullable();
            $table->boolean('is_qantas')->default(false);
            $table->date('join_date');
            $table->string('salary')->default('0');
            $table->date('contract_end')->nullable();
            $table->date('pas_expired')->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();
        });

        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('radius')->default(40);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('description');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('use_manpower')->default(1);
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->boolean('is_active')->default(true);
            $table->date('date');
            $table->string('shift_id');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('station_id')->nullable();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->timestamps();
        });

        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('airline');
            $table->string('flight_number');
            $table->string('registasi');
            $table->string('type');
            $table->time('arrival');
            $table->time('time_count');
            $table->string('station');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('flight_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_id');
            $table->foreignId('schedule_id');
            $table->timestamps();
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('leave_type');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('work_results', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('station');
            $table->string('aircraft_reg');
            $table->string('ex_flight')->nullable();
            $table->string('to_flight')->nullable();
            $table->string('parking_stand');
            $table->string('wo_number');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('photo_path')->nullable();
            $table->string('type');
            $table->string('submitted_by', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('work_result_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_result_id');
            $table->string('user_id', 20);
            $table->timestamps();
        });
    }
}
