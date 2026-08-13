<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveWorkflowTest extends TestCase
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

    private function createUser(string $id, string $fullname, string $role, ?string $email = null): User
    {
        return User::create([
            'id' => $id,
            'fullname' => $fullname,
            'email' => $email ?? "user_{$id}@test.com",
            'password' => bcrypt('password'),
            'role' => $role,
            'station' => 'CGK',
            'gender' => 'Male',
            'join_date' => '2024-01-01',
            'is_active' => 1,
        ]);
    }

    public function test_leave_approval_workflow_requires_two_tiers(): void
    {
        $hoas   = $this->createUser('10003', 'Test HOAS', 'Head Of Airport Service');
        $leader = $this->createUser('10002', 'Test Leader Apron', 'Leader Apron');
        $leader->update(['pic_id' => $hoas->id]);
        $porter = $this->createUser('10001', 'Test Porter Apron', 'Porter Apron');
        $porter->update(['pic_id' => $leader->id]);

        // 1. Porter submits leave
        $leave = Leave::create([
            'user_id' => $porter->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(7),
            'total_days' => 3,
            'reason' => 'Acara keluarga',
            'status' => 'pending Apron',
        ]);

        // 2. Leader Apron logs in and visits approval page
        // They should see the leave request (status is pending Apron)
        $response = $this->actingAs($leader)->get(route('leaves.index'));
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('leaves')->contains('id', $leave->id));

        // 3. Leader Apron approves it -> Transitions to pending (Menunggu HO)
        $responseApprove = $this->actingAs($leader)->patch(route('leaves.updateStatus', $leave->id), [
            'status' => 'pending',
        ]);
        $responseApprove->assertRedirect();
        
        $leave->refresh();
        $this->assertEquals('pending', $leave->status);

        // 4. Leader Apron visits approval page again
        // They should NOT see the leave request anymore (since status is pending, not pending Apron)
        $response2 = $this->actingAs($leader)->get(route('leaves.index'));
        $response2->assertStatus(200);
        $this->assertFalse($response2->viewData('leaves')->contains('id', $leave->id));

        // 5. Leader Apron tries to approve it again or directly to approved -> should be blocked
        $responseDoubleApprove = $this->actingAs($leader)->patch(route('leaves.updateStatus', $leave->id), [
            'status' => 'approved',
        ]);
        $responseDoubleApprove->assertStatus(403);

        // 6. HOAS logs in and visits approval page
        // HOAS should see the leave request (status is pending)
        $responseHoas = $this->actingAs($hoas)->get(route('leaves.index'));
        $responseHoas->assertStatus(200);
        $this->assertTrue($responseHoas->viewData('leaves')->contains('id', $leave->id));

        // 7. HOAS approves it -> Transitions to approved
        $responseHoasApprove = $this->actingAs($hoas)->patch(route('leaves.updateStatus', $leave->id), [
            'status' => 'approved',
        ]);
        $responseHoasApprove->assertRedirect();

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($hoas->id, $leave->approved_by);
    }

    public function test_non_admin_cannot_self_approve_their_own_leave(): void
    {
        $hoas = $this->createUser('10003', 'Test HOAS', 'Head Of Airport Service');

        $leave = Leave::create([
            'user_id' => $hoas->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(7),
            'total_days' => 3,
            'reason' => 'Acara keluarga',
            'status' => 'pending',
        ]);

        // HOAS should not see their own leave request on the approval list
        $response = $this->actingAs($hoas)->get(route('leaves.index'));
        $response->assertStatus(200);
        $this->assertFalse($response->viewData('leaves')->contains('id', $leave->id));

        // HOAS trying to approve it anyway should receive 403 Forbidden
        $responseApprove = $this->actingAs($hoas)->patch(route('leaves.updateStatus', $leave->id), [
            'status' => 'approved',
        ]);
        $responseApprove->assertStatus(403);
    }

    public function test_admin_can_self_approve_their_own_leave(): void
    {
        $admin = $this->createUser('10004', 'Test Admin User', 'Admin');

        $leave = Leave::create([
            'user_id' => $admin->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(7),
            'total_days' => 3,
            'reason' => 'Liburan',
            'status' => 'pending',
        ]);

        // Admin should see their own leave request on the approval list
        $response = $this->actingAs($admin)->get(route('leaves.index'));
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('leaves')->contains('id', $leave->id));

        // Admin should be allowed to self-approve
        $responseApprove = $this->actingAs($admin)->patch(route('leaves.updateStatus', $leave->id), [
            'status' => 'approved',
        ]);
        $responseApprove->assertRedirect();

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
    }

    public function test_leave_submission_allows_up_to_7_days_backdate(): void
    {
        $porter = $this->createUser('10005', 'Test Porter Backdate', 'Porter Apron');
        $leaveType = \App\Models\LeaveType::create([
            'name' => 'Cuti Tahunan Test',
            'gender_restriction' => 'All',
            'default_quota' => 12,
            'is_unlimited' => false,
            'is_active' => true,
        ]);

        // 1. Backdate 5 days ago (should pass validation)
        $responseSuccess = $this->actingAs($porter)->post(route('leaves.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => Carbon::today()->subDays(5)->format('Y-m-d'),
            'end_date'      => Carbon::today()->subDays(3)->format('Y-m-d'),
            'reason'        => 'Izin urusan keluarga mendesak',
        ]);
        $responseSuccess->assertSessionHasNoErrors();

        // 2. Backdate 8 days ago (should fail validation)
        $responseFail = $this->actingAs($porter)->post(route('leaves.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => Carbon::today()->subDays(8)->format('Y-m-d'),
            'end_date'      => Carbon::today()->subDays(6)->format('Y-m-d'),
            'reason'        => 'Izin terlambat',
        ]);
        $responseFail->assertSessionHasErrors(['start_date']);
    }
}
