<?php

namespace Tests\Feature;

use App\Models\LeaveType;
use App\Models\LeaveRule;
use App\Models\LeaveBalance;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterLeaveTest extends TestCase
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
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function createAdmin(): User
    {
        return User::create([
            'id' => 'ADMIN01',
            'fullname' => 'System Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'station' => 'CGK',
            'gender' => 'Male',
            'join_date' => '2024-01-01',
            'is_active' => 1,
        ]);
    }

    private function createStaff(string $id, string $gender = 'Male', string $joinDate = '2024-01-01'): User
    {
        return User::create([
            'id' => $id,
            'fullname' => 'Staff ' . $id,
            'email' => "staff_{$id}@test.com",
            'password' => bcrypt('password'),
            'role' => 'Porter Apron',
            'station' => 'CGK',
            'gender' => $gender,
            'join_date' => $joinDate,
            'is_active' => 1,
        ]);
    }

    public function test_non_admin_cannot_access_master_leaves(): void
    {
        $staff = $this->createStaff('10001');

        $response = $this->actingAs($staff)->get(route('master_leaves.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_manage_leave_types_and_rules(): void
    {
        $admin = $this->createAdmin();
        $staff = $this->createStaff('10002', 'Male', '2024-01-01');

        // 1. Access Index
        $response = $this->actingAs($admin)->get(route('master_leaves.index'));
        $response->assertStatus(200);

        // 2. Create Leave Type
        $responseCreate = $this->actingAs($admin)->post(route('master_leaves.store'), [
            'name' => 'Cuti Uji Coba',
            'default_quota' => 5,
            'gender_restriction' => 'All',
            'is_unlimited' => 0,
        ]);
        $responseCreate->assertRedirect();
        
        $leaveType = LeaveType::where('name', 'Cuti Uji Coba')->first();
        $this->assertNotNull($leaveType);
        $this->assertEquals('Cuti Uji Coba', $leaveType->name);

        // Check if balance automatically created/synced for the staff
        $balance = LeaveBalance::where('user_id', $staff->id)->where('leave_type_id', $leaveType->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(5, $balance->remaining_days);

        // 3. Update Leave Type
        $responseUpdate = $this->actingAs($admin)->put(route('master_leaves.update', $leaveType->id), [
            'name' => 'Cuti Uji Coba Baru',
            'default_quota' => 7,
            'gender_restriction' => 'Male',
            'is_unlimited' => 0,
            'is_active' => 1,
        ]);
        $responseUpdate->assertRedirect();

        $leaveType->refresh();
        $this->assertEquals('Cuti Uji Coba Baru', $leaveType->name);
        $this->assertEquals('Male', $leaveType->gender_restriction);

        // 4. Add Rule
        $responseAddRule = $this->actingAs($admin)->post(route('master_leaves.rules.store', $leaveType->id), [
            'min_tenure_years' => 1,
            'max_tenure_years' => 2,
            'quota_days' => 10,
            'description' => 'Tenure rule 1-2 years',
        ]);
        $responseAddRule->assertRedirect();

        $rule = LeaveRule::where('leave_type_id', $leaveType->id)->first();
        $this->assertNotNull($rule);
        $this->assertEquals(10, $rule->quota_days);

        // 5. Update Rule
        $responseUpdateRule = $this->actingAs($admin)->put(route('master_leaves.rules.update', $rule->id), [
            'min_tenure_years' => 1,
            'max_tenure_years' => 3,
            'quota_days' => 15,
            'description' => 'Tenure rule 1-3 years updated',
        ]);
        $responseUpdateRule->assertRedirect();

        $rule->refresh();
        $this->assertEquals(3, $rule->max_tenure_years);
        $this->assertEquals(15, $rule->quota_days);

        // 6. Delete Rule
        $responseDeleteRule = $this->actingAs($admin)->delete(route('master_leaves.rules.destroy', $rule->id));
        $responseDeleteRule->assertRedirect();
        $this->assertNull(LeaveRule::find($rule->id));
    }

    public function test_gender_restriction_validation_blocks_ineligible_requests(): void
    {
        $admin = $this->createAdmin();
        $maleStaff = $this->createStaff('10003', 'Male');
        $femaleStaff = $this->createStaff('10004', 'Female');

        $maternityType = LeaveType::create([
            'name' => 'Cuti Hamil Test',
            'default_quota' => 90,
            'gender_restriction' => 'Female',
            'is_unlimited' => false,
        ]);

        // Male user trying to apply maternity leave -> should fail verifyEligibility validation
        $responseMale = $this->actingAs($maleStaff)->post(route('leaves.store'), [
            'leave_type_id' => $maternityType->id,
            'start_date' => Carbon::now()->addDays(5)->toDateString(),
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'reason' => 'Melahirkan',
        ]);
        $responseMale->assertRedirect();
        $responseMale->assertSessionHas('alert');
    }

    public function test_non_admin_cannot_modify_master_leaves(): void
    {
        $hoas = User::create([
            'id' => 'HOAS01',
            'fullname' => 'Head of Airport Service',
            'email' => 'hoas@test.com',
            'password' => bcrypt('password'),
            'role' => 'Head Of Airport Service',
            'station' => 'CGK',
            'gender' => 'Male',
            'join_date' => '2024-01-01',
            'is_active' => 1,
        ]);

        // HOAS can view master leaves index
        $responseView = $this->actingAs($hoas)->get(route('master_leaves.index'));
        $responseView->assertStatus(200);

        // HOAS CANNOT store a new leave type
        $responseStore = $this->actingAs($hoas)->post(route('master_leaves.store'), [
            'name' => 'Unauthorized Type',
            'default_quota' => 5,
            'gender_restriction' => 'All',
            'is_unlimited' => 0,
        ]);
        $responseStore->assertStatus(403);
    }

    public function test_rules_page_requires_permissions(): void
    {
        $staff = $this->createStaff('10005');
        $admin = $this->createAdmin();
        $leaveType = LeaveType::create([
            'name' => 'Cuti Khusus',
            'default_quota' => 3,
            'gender_restriction' => 'All',
            'is_unlimited' => false,
        ]);

        // Non-admin/unauthorized user is blocked
        $response = $this->actingAs($staff)->get(route('master_leaves.rules.index', $leaveType->id));
        $response->assertStatus(403);

        // Admin can view the rules page
        $responseAdmin = $this->actingAs($admin)->get(route('master_leaves.rules.index', $leaveType->id));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Cuti Khusus');

        // Admin can view leave balances page
        $responseBalances = $this->actingAs($admin)->get(route('leaves.balances'));
        $responseBalances->assertStatus(200);
        $responseBalances->assertSee('Daftar Saldo Cuti Karyawan');
    }
}
