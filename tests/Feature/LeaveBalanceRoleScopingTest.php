<?php

namespace Tests\Feature;

use App\Models\LeaveType;
use App\Models\Station;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceRoleScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Station::create([
            'name' => 'Cengkareng',
            'code' => 'CGK',
            'latitude' => -6.1256,
            'longitude' => 106.6558,
            'is_active' => 1,
        ]);

        Station::create([
            'name' => 'Surabaya',
            'code' => 'SUB',
            'latitude' => -7.3798,
            'longitude' => 112.7875,
            'is_active' => 1,
        ]);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        LeaveType::create([
            'name' => 'Cuti Tahunan',
            'gender_restriction' => 'All',
            'default_quota' => 12,
            'is_unlimited' => false,
            'is_active' => true,
        ]);
    }

    private function createUserWithEmployee(string $id, string $fullname, string $role, string $station): User
    {
        $employee = Employee::create([
            'id' => $id,
            'fullname' => $fullname,
            'station_id' => $station,
            'gender' => 'Male',
            'join_date' => '2024-01-01',
        ]);

        return User::create([
            'id' => $id,
            'employee_id' => $employee->id,
            'fullname' => $fullname,
            'email' => "user_{$id}@test.com",
            'password' => bcrypt('password'),
            'role' => $role,
            'station' => $station,
            'gender' => 'Male',
            'join_date' => '2024-01-01',
            'is_active' => 1,
        ]);
    }

    public function test_staff_porter_only_sees_own_leave_balance(): void
    {
        $porter1 = $this->createUserWithEmployee('1001', 'Indra Porter', 'Porter Bge', 'CGK');
        $porter2 = $this->createUserWithEmployee('1002', 'Budi Porter', 'Porter Bge', 'CGK');
        $admin = $this->createUserWithEmployee('9999', 'Admin User', 'Admin', 'CGK');

        // Default view: shows only self
        $response = $this->actingAs($porter1)->get(route('leaves.balances'));
        $response->assertStatus(200);
        $response->assertSee('Indra Porter');
        $response->assertDontSee('Budi Porter');
        $response->assertDontSee('Admin User');

        // Submitting with empty search: must still show only self
        $responseEmptySearch = $this->actingAs($porter1)->get(route('leaves.balances', ['year' => 2026, 'search' => '']));
        $responseEmptySearch->assertStatus(200);
        $responseEmptySearch->assertSee('Indra Porter');
        $responseEmptySearch->assertDontSee('Budi Porter');
        $responseEmptySearch->assertDontSee('Admin User');

        // Attempting to bypass via scope=all or station_id: porter is strictly locked to self
        $responseScopeAll = $this->actingAs($porter1)->get(route('leaves.balances', ['scope' => 'all', 'station_id' => 'CGK']));
        $responseScopeAll->assertStatus(200);
        $responseScopeAll->assertSee('Indra Porter');
        $responseScopeAll->assertDontSee('Budi Porter');
        $responseScopeAll->assertDontSee('Admin User');

        // Attempting to search for other user: porter cannot view other staff
        $responseSearch = $this->actingAs($porter1)->get(route('leaves.balances', ['search' => 'Budi']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertDontSee('Budi Porter');
        $responseSearch->assertSee('Tidak ada data karyawan / saldo cuti ditemukan');
    }

    public function test_leader_bge_defaults_to_self_and_can_view_bge_subordinates(): void
    {
        $leader = $this->createUserWithEmployee('2001', 'Ahmad Leader Bge', 'Leader Bge', 'CGK');
        $subBge = $this->createUserWithEmployee('2002', 'Doni Subordinate Bge', 'Porter Bge', 'CGK');
        $subApron = $this->createUserWithEmployee('2003', 'Eko Subordinate Apron', 'Porter Apron', 'CGK');

        // Default view: shows only leader self
        $responseDefault = $this->actingAs($leader)->get(route('leaves.balances'));
        $responseDefault->assertStatus(200);
        $responseDefault->assertSee('Ahmad Leader Bge');
        $responseDefault->assertDontSee('Doni Subordinate Bge');
        $responseDefault->assertDontSee('Eko Subordinate Apron');

        // Searching for subordinate by name/NIP
        $responseSearch = $this->actingAs($leader)->get(route('leaves.balances', ['search' => 'Doni']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Doni Subordinate Bge');
        // Leader Bge cannot see Apron subordinates
        $responseSearchApron = $this->actingAs($leader)->get(route('leaves.balances', ['search' => 'Eko']));
        $responseSearchApron->assertStatus(200);
        $responseSearchApron->assertDontSee('Eko Subordinate Apron');
    }

    public function test_admin_defaults_to_self_and_can_view_all_users_and_filter_by_station(): void
    {
        $admin = $this->createUserWithEmployee('9001', 'Super Admin', 'Admin', 'CGK');
        $staffCgk = $this->createUserWithEmployee('9002', 'Staff CGK', 'Staff', 'CGK');
        $staffSub = $this->createUserWithEmployee('9003', 'Staff SUB', 'Staff', 'SUB');

        // Default view: shows only admin self
        $responseDefault = $this->actingAs($admin)->get(route('leaves.balances'));
        $responseDefault->assertStatus(200);
        $responseDefault->assertSee('Super Admin');
        $responseDefault->assertDontSee('Staff CGK');
        $responseDefault->assertDontSee('Staff SUB');

        // Searching by name/NIP
        $responseSearch = $this->actingAs($admin)->get(route('leaves.balances', ['search' => 'Staff']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Staff CGK');
        $responseSearch->assertSee('Staff SUB');

        // Filtering by station SUB
        $responseSub = $this->actingAs($admin)->get(route('leaves.balances', ['station_id' => 'SUB']));
        $responseSub->assertStatus(200);
        $responseSub->assertSee('Staff SUB');
        $responseSub->assertDontSee('Staff CGK');
    }
}
