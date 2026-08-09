<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-26 12:00:00');

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fullname');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('gender')->default('Male');
            $table->string('role');
            $table->string('station');
            $table->string('manager')->nullable();
            $table->date('join_date');
            $table->string('salary')->default('0');
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

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->string('check_in_ip')->nullable();
            $table->string('check_in_latitude')->nullable();
            $table->string('check_in_longitude')->nullable();
            $table->string('status')->nullable();
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

        $this->runFeatureMigrationsIfPresent();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_correction_persists_string_user_key_station_and_unique_date(): void
    {
        [$user, $station] = $this->makeUserAndStation();

        AttendanceCorrection::create([
            'user_id' => $user->id,
            'station_id' => $station->id,
            'attendance_date' => '2026-07-20',
            'proposed_check_in_time' => '2026-07-20 08:00:00',
            'proposed_check_out_time' => '2026-07-20 17:00:00',
            'reason' => 'Mesin absensi tidak mencatat.',
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'user_id' => $user->id,
            'station_id' => $station->id,
            'attendance_date' => '2026-07-20',
        ]);
    }

    public function test_user_can_submit_one_valid_correction_for_own_date(): void
    {
        [$user, $station] = $this->makeUserAndStation();

        $this->actingAs($user)
            ->post(route('attendance.corrections.store', '2026-07-20'), [
                'check_in_time' => '08:00',
                'check_out_time' => '17:00',
                'station_id' => $station->id,
                'reason' => 'Mesin absensi tidak mencatat.',
            ])
            ->assertRedirect(route('attendance.history'));

        $this->assertDatabaseHas('attendance_corrections', [
            'user_id' => $user->id,
            'attendance_date' => '2026-07-20',
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);
    }

    public function test_submission_rejects_future_date_invalid_times_and_inactive_station(): void
    {
        [$user, $station] = $this->makeUserAndStation();
        $inactiveStation = Station::create([
            'code' => 'SUB',
            'name' => 'Surabaya',
            'is_active' => false,
            'latitude' => -7.379831,
            'longitude' => 112.786819,
            'radius' => 40,
        ]);

        $this->actingAs($user)
            ->from(route('attendance.history'))
            ->post(route('attendance.corrections.store', '2026-07-27'), [
                'check_in_time' => '17:00',
                'check_out_time' => '08:00',
                'station_id' => $inactiveStation->id,
                'reason' => 'Data salah.',
            ])
            ->assertRedirect(route('attendance.history'))
            ->assertSessionHasErrors('attendance_date');

        $this->actingAs($user)
            ->from(route('attendance.history'))
            ->post(route('attendance.corrections.store', '2026-07-20'), [
                'check_in_time' => '17:61',
                'check_out_time' => 'bukan-jam',
                'station_id' => $inactiveStation->id,
                'reason' => 'Data salah.',
            ])
            ->assertRedirect(route('attendance.history'))
            ->assertSessionHasErrors([
                'check_in_time',
                'check_out_time',
                'station_id',
            ]);
    }

    public function test_user_cannot_submit_again_after_request_was_rejected(): void
    {
        [$user, $station] = $this->makeUserAndStation();

        AttendanceCorrection::create([
            'user_id' => $user->id,
            'station_id' => $station->id,
            'attendance_date' => '2026-07-20',
            'proposed_check_in_time' => '2026-07-20 08:00:00',
            'proposed_check_out_time' => '2026-07-20 17:00:00',
            'reason' => 'Pengajuan pertama.',
            'status' => AttendanceCorrection::STATUS_REJECTED,
        ]);

        $this->actingAs($user)
            ->from(route('attendance.history'))
            ->post(route('attendance.corrections.store', '2026-07-20'), [
                'check_in_time' => '09:00',
                'check_out_time' => '18:00',
                'station_id' => $station->id,
                'reason' => 'Pengajuan kedua.',
            ])
            ->assertRedirect(route('attendance.history'))
            ->assertSessionHasErrors('attendance_date');

        $this->assertDatabaseCount('attendance_corrections', 1);
    }

    public function test_configured_manager_can_approve_and_create_missing_attendance(): void
    {
        [$manager, $applicant, $station, $correction] = $this->makePendingCorrection();

        $this->actingAs($manager)
            ->post(route('attendance.corrections.approve', $correction))
            ->assertRedirect();

        $attendance = Attendance::where('user_id', $applicant->id)->firstOrFail();

        $this->assertSame($station->id, $attendance->station_id);
        $this->assertSame('2026-07-20 08:00:00', $attendance->check_in_time);
        $this->assertSame('2026-07-20 17:00:00', $attendance->check_out_time);
        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'attendance_id' => $attendance->id,
            'status' => AttendanceCorrection::STATUS_APPROVED,
            'decided_by' => $manager->id,
        ]);
    }

    public function test_approval_updates_existing_attendance(): void
    {
        [$manager, $applicant, $station, $correction, $attendance] = $this->makePendingCorrection(true);

        $this->actingAs($manager)
            ->post(route('attendance.corrections.approve', $correction))
            ->assertRedirect();

        $attendance->refresh();

        $this->assertSame('2026-07-20 08:00:00', $attendance->check_in_time);
        $this->assertSame('2026-07-20 17:00:00', $attendance->check_out_time);
        $this->assertSame($station->id, $attendance->station_id);
        $this->assertSame($attendance->id, $correction->fresh()->attendance_id);
    }

    public function test_rejection_is_final_and_leaves_attendance_unchanged(): void
    {
        [$manager, , , $correction, $attendance] = $this->makePendingCorrection(true);

        $this->actingAs($manager)
            ->post(route('attendance.corrections.reject', $correction), [
                'rejection_reason' => 'Jam tidak sesuai dengan bukti',
            ])
            ->assertRedirect();

        $this->assertSame('2026-07-20 09:00:00', $attendance->fresh()->check_in_time);
        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'status' => AttendanceCorrection::STATUS_REJECTED,
            'rejection_reason' => 'Jam tidak sesuai dengan bukti',
            'decided_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('attendance.corrections.approve', $correction))
            ->assertStatus(409);
    }

    public function test_unrelated_user_cannot_view_or_decide_a_correction(): void
    {
        [, , , $correction] = $this->makePendingCorrection();
        $unrelated = $this->makeUser('109999', 'Bukan Manager', 'Leader Bge');

        $this->actingAs($unrelated)
            ->get(route('attendance.corrections.approval'))
            ->assertDontSee('Staff Koreksi');

        $this->actingAs($unrelated)
            ->post(route('attendance.corrections.approve', $correction))
            ->assertForbidden();
    }

    public function test_admin_can_view_and_approve_any_correction(): void
    {
        [, $applicant, , $correction] = $this->makePendingCorrection();
        $admin = $this->makeUser('100001', 'Administrator', 'Admin');

        $this->actingAs($admin)
            ->get(route('attendance.corrections.approval'))
            ->assertOk()
            ->assertSee($applicant->fullname);

        $this->actingAs($admin)
            ->post(route('attendance.corrections.approve', $correction))
            ->assertRedirect();

        $this->assertSame(AttendanceCorrection::STATUS_APPROVED, $correction->fresh()->status);
    }

    public function test_physical_check_in_saves_resolved_station_id(): void
    {
        [$user, $station] = $this->makeUserAndStation();

        DB::table('shifts')->insert([
            'id' => 'P',
            'name' => 'Pagi',
            'description' => 'Shift Pagi',
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHours(8)->format('H:i:s'),
            'use_manpower' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('schedules')->insert([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'shift_id' => 'P',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('attendance.checkIn'), [
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'station_id' => $station->id,
        ]);
    }

    public function test_history_shows_edit_once_and_form_lists_only_active_offices(): void
    {
        [$user, $station] = $this->makeUserAndStation();
        Station::create([
            'code' => 'SUB',
            'name' => 'Surabaya',
            'is_active' => false,
            'latitude' => -7.379831,
            'longitude' => 112.786819,
            'radius' => 40,
        ]);

        $correctionUrl = route('attendance.corrections.create', '2026-07-20');

        $this->actingAs($user)
            ->get(route('attendance.history', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee($correctionUrl, false);

        $this->actingAs($user)
            ->get($correctionUrl)
            ->assertOk()
            ->assertSee($station->code)
            ->assertDontSee('Surabaya');

        AttendanceCorrection::create([
            'user_id' => $user->id,
            'station_id' => $station->id,
            'attendance_date' => '2026-07-20',
            'proposed_check_in_time' => '2026-07-20 08:00:00',
            'proposed_check_out_time' => '2026-07-20 17:00:00',
            'reason' => 'Sekali saja.',
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('attendance.history', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('Pending')
            ->assertDontSee($correctionUrl, false);
    }

    public function test_correction_form_uses_time_only_inputs(): void
    {
        [$user] = $this->makeUserAndStation();

        $response = $this->actingAs($user)
            ->get(route('attendance.corrections.create', '2026-07-20'))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/<input\s+type="time"\s+id="check_in_time"/',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<input\s+type="time"\s+id="check_out_time"/',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<input\s+type="datetime-local"\s+id="check_(?:in|out)_time"/',
            $response->getContent()
        );
    }

    public function test_time_only_overnight_submission_uses_fixed_date_and_next_day_checkout(): void
    {
        [$user, $station] = $this->makeUserAndStation();

        $this->actingAs($user)
            ->post(route('attendance.corrections.store', '2026-07-20'), [
                'check_in_time' => '22:00',
                'check_out_time' => '06:00',
                'station_id' => $station->id,
                'reason' => 'Shift malam.',
            ])
            ->assertRedirect(route('attendance.history'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_corrections', [
            'user_id' => $user->id,
            'attendance_date' => '2026-07-20',
            'proposed_check_in_time' => '2026-07-20 22:00:00',
            'proposed_check_out_time' => '2026-07-21 06:00:00',
        ]);
    }

    public function test_history_displays_correction_note_and_placeholder(): void
    {
        [$user, $station] = $this->makeUserAndStation();

        AttendanceCorrection::create([
            'user_id' => $user->id,
            'station_id' => $station->id,
            'attendance_date' => '2026-07-20',
            'proposed_check_in_time' => '2026-07-20 08:00:00',
            'proposed_check_out_time' => '2026-07-20 17:00:00',
            'reason' => 'Lupa absen karena perangkat mati.',
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.history', ['month' => '2026-07']))
            ->assertOk();

        $this->assertStringContainsString('Note Koreksi', $response->getContent());
        $this->assertStringContainsString(
            'Lupa absen karena perangkat mati.',
            $response->getContent()
        );
        $this->assertStringContainsString(
            'class="correction-note"',
            $response->getContent()
        );
    }

    private function makeUserAndStation(): array
    {
        $station = Station::create([
            'code' => 'CGK',
            'name' => 'Jakarta',
            'is_active' => true,
            'latitude' => -6.2354184,
            'longitude' => 106.7780129,
            'radius' => 40,
        ]);

        $user = User::create([
            'id' => '101001',
            'fullname' => 'Staff Satu',
            'email' => 'staff@example.com',
            'password' => 'password',
            'is_active' => true,
            'gender' => 'Male',
            'role' => 'Porter Apron',
            'station' => 'CGK',
            'manager' => 'Manager Satu',
            'join_date' => '2026-01-01',
            'salary' => '0',
        ]);

        return [$user, $station];
    }

    private function makePendingCorrection(bool $withAttendance = false): array
    {
        [, $station] = $this->makeUserAndStation();
        $applicant = User::findOrFail('101001');
        $applicant->update([
            'fullname' => 'Staff Koreksi',
            'manager' => 'Manager Satu',
        ]);
        $manager = $this->makeUser('102001', 'Manager Satu', 'Leader Apron');

        $attendance = null;

        if ($withAttendance) {
            $attendance = Attendance::create([
                'user_id' => $applicant->id,
                'check_in_time' => '2026-07-20 09:00:00',
                'check_out_time' => '2026-07-20 16:00:00',
            ]);
        }

        $correction = AttendanceCorrection::create([
            'user_id' => $applicant->id,
            'attendance_id' => $attendance?->id,
            'station_id' => $station->id,
            'attendance_date' => '2026-07-20',
            'proposed_check_in_time' => '2026-07-20 08:00:00',
            'proposed_check_out_time' => '2026-07-20 17:00:00',
            'reason' => 'Mesin absensi tidak mencatat.',
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);

        return [$manager, $applicant, $station, $correction, $attendance];
    }

    private function makeUser(string $id, string $fullname, string $role, ?string $manager = null): User
    {
        return User::create([
            'id' => $id,
            'fullname' => $fullname,
            'email' => strtolower($id).'@example.com',
            'password' => 'password',
            'is_active' => true,
            'gender' => 'Male',
            'role' => $role,
            'station' => 'CGK',
            'manager' => $manager,
            'join_date' => '2026-01-01',
            'salary' => '0',
        ]);
    }

    private function runFeatureMigrationsIfPresent(): void
    {
        $paths = [
            database_path('migrations/2026_07_26_120000_add_station_id_to_attendances_table.php'),
            database_path('migrations/2026_07_26_120100_create_attendance_corrections_table.php'),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                (require $path)->up();
            }
        }
    }

    public function test_cannot_submit_attendance_correction_when_on_active_leave(): void
    {
        [$applicant, $station] = $this->makeUserAndStation();

        // Create leaves table in test SQLite database
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('leave_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->string('status');
            $table->timestamps();
        });

        // Create active leave
        \App\Models\Leave::create([
            'user_id' => $applicant->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-20',
            'total_days' => 1,
            'reason' => 'Holiday',
            'status' => 'approved',
        ]);

        $this->actingAs($applicant)
            ->post(route('attendance.corrections.store', '2026-07-20'), [
                'check_in_time' => '08:00',
                'check_out_time' => '17:00',
                'station_id' => $station->id,
                'reason' => 'Mesin absensi tidak mencatat.',
            ])
            ->assertRedirect();

        // Ensure correction was not created
        $this->assertDatabaseMissing('attendance_corrections', [
            'user_id' => $applicant->id,
            'attendance_date' => '2026-07-20',
        ]);
    }
}
