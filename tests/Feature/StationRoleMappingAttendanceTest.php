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

        $this->assertStringContainsString('belum di-mapping', $response->json('message'));
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

    public function test_admin_bypasses_station_role_mapping(): void
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

        $this->assertTrue($station->isRoleAllowed($adminUser));
    }
}
