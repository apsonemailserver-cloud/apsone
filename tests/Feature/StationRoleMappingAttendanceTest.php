<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StationRoleMappingAttendanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('Staff');
            $table->string('station')->default('CGK');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->decimal('latitude', 10, 7)->default(-6.1256);
            $table->decimal('longitude', 10, 7)->default(106.6558);
            $table->integer('radius')->default(5000);
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->unsignedBigInteger('station_id')->nullable();
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->timestamps();
        });
    }

    public function test_user_is_blocked_from_check_in_if_role_not_mapped_to_station(): void
    {
        $station = Station::create([
            'code' => 'CGK',
            'name' => 'Jakarta (Soekarno-Hatta)',
            'is_active' => true,
            'role' => 'Porter Bge, Porter Apron',
        ]);

        $unmappedUser = User::create([
            'id' => '1001',
            'name' => 'Driver User',
            'role' => 'Driver',
            'station' => 'CGK',
            'is_active' => 1,
        ]);

        $this->assertFalse($station->isRoleAllowed($unmappedUser));

        $response = $this->actingAs($unmappedUser)
            ->postJson(route('attendance.checkIn'), [
                'latitude' => -6.1256,
                'longitude' => 106.6558,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('belum terdaftar', $response->json('message'));
    }

    public function test_user_can_pass_station_validation_if_role_is_mapped(): void
    {
        $station = Station::create([
            'code' => 'CGK',
            'name' => 'Jakarta (Soekarno-Hatta)',
            'is_active' => true,
            'role' => 'Porter Bge, Porter Apron',
        ]);

        $mappedUser = User::create([
            'id' => '1002',
            'name' => 'Porter User',
            'role' => 'Porter Bge',
            'station' => 'CGK',
            'is_active' => 1,
        ]);

        $this->assertTrue($station->isRoleAllowed($mappedUser));
    }

    public function test_unmapped_admin_is_blocked_from_check_in_if_not_in_station_roles(): void
    {
        $station = Station::create([
            'code' => 'CGK',
            'name' => 'Jakarta (Soekarno-Hatta)',
            'is_active' => true,
            'role' => 'Porter Bge',
        ]);

        $adminUser = User::create([
            'id' => '1003',
            'name' => 'Admin User',
            'role' => 'Admin',
            'station' => 'CGK',
            'is_active' => 1,
        ]);

        $this->assertFalse($station->isRoleAllowed($adminUser));
    }

    public function test_updating_station_roles_saves_to_database(): void
    {
        $adminUser = User::create([
            'id' => '9999',
            'name' => 'Admin User 2',
            'role' => 'Admin',
            'station' => 'CGK',
            'is_active' => 1,
        ]);

        $station = Station::create([
            'code' => 'CGK',
            'name' => 'Jakarta (Soekarno-Hatta)',
            'is_active' => true,
            'latitude' => -6.1256,
            'longitude' => 106.6558,
            'radius' => 5000,
            'role' => null,
        ]);

        $response = $this->actingAs($adminUser)->post(route('stations.update', $station->id), [
            'latitude' => -6.1256,
            'longitude' => 106.6558,
            'radius' => 5000,
            'role' => ['Porter Bge', 'Leader Apron'],
        ]);

        $response->assertRedirect(route('stations.index'));
        $this->assertSame('Porter Bge, Leader Apron', $station->fresh()->role);
    }

    public function test_user_is_blocked_from_check_in_if_on_active_leave(): void
    {
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

        $user = User::create([
            'id' => '1004',
            'name' => 'Leave User',
            'role' => 'Porter Bge',
            'station' => 'CGK',
            'is_active' => 1,
        ]);

        // Create active leave for today
        \App\Models\Leave::create([
            'user_id' => $user->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'total_days' => 1,
            'reason' => 'Holiday',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('attendance.checkIn'), [
                'latitude' => -6.1256,
                'longitude' => 106.6558,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Absensi gagal! Anda tidak dapat melakukan absensi karena sedang dalam masa cuti.'
            ]);
    }
}
