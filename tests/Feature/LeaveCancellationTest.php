<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCancellationTest extends TestCase
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
    }

    private function createStaffUser(string $id = '10240455'): User
    {
        return User::create([
            'id' => $id,
            'fullname' => 'Test Staff ' . $id,
            'email' => "staff_{$id}@test.com",
            'password' => bcrypt('password'),
            'role' => 'Porter Apron',
            'station' => 'CGK',
            'gender' => 'Male',
            'join_date' => '2024-01-01',
            'is_active' => 1,
        ]);
    }

    private function createAdminUser(): User
    {
        return User::create([
            'id' => 'ADMIN01',
            'fullname' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'station' => 'CGK',
            'gender' => 'Male',
            'join_date' => '2024-01-01',
            'is_active' => 1,
        ]);
    }

    public function test_user_can_cancel_pending_leave_request(): void
    {
        $user = $this->createStaffUser('10001');

        $leave = Leave::create([
            'user_id' => $user->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(7),
            'total_days' => 3,
            'reason' => 'Acara keluarga',
            'status' => 'pending Apron',
        ]);

        $response = $this->actingAs($user)->patch(route('leaves.cancel', $leave->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'canceled',
        ]);
    }

    public function test_approved_leave_deducts_quota_and_admin_cancelling_refunds_quota(): void
    {
        $user = $this->createStaffUser('10002');
        $admin = $this->createAdminUser();

        // Initially 0 used days -> Balance is 12 hari
        $response1 = $this->actingAs($user)->get(route('leaves.pengajuan'));
        $response1->assertSee('12 hari');

        // Create an approved leave request of 2 days
        $leave = Leave::create([
            'user_id' => $user->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(11),
            'total_days' => 2,
            'reason' => 'Liburan',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        // Sisa cuti should now be 10 hari (12 - 2)
        $response2 = $this->actingAs($user)->get(route('leaves.pengajuan'));
        $response2->assertSee('10 hari');
        $response2->assertSee('2 hari'); // Terpakai

        // Staff (bawahan) cannot cancel approved leave directly
        $staffCancelResponse = $this->actingAs($user)->patch(route('leaves.cancel', $leave->id));
        $staffCancelResponse->assertRedirect(); // Redirects with warning alert

        // Admin (atasan) cancels the approved leave
        $adminCancelResponse = $this->actingAs($admin)->patch(route('leaves.cancel', $leave->id));
        $adminCancelResponse->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'canceled',
        ]);

        // After cancellation, leave quota should be REFUNDED back to 12 hari (0 terpakai)
        $refundCheck = $this->actingAs($user)->get(route('leaves.pengajuan'));
        $refundCheck->assertSee('12 hari');
        $refundCheck->assertSee('0 hari');
    }

    public function test_user_cannot_cancel_another_users_leave_request(): void
    {
        $user1 = $this->createStaffUser('10003');
        $user2 = $this->createStaffUser('10004');

        $leave = Leave::create([
            'user_id' => $user1->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(6),
            'total_days' => 2,
            'reason' => 'Testing',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user2)->patch(route('leaves.cancel', $leave->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'pending',
        ]);
    }

    public function test_rejected_leave_cannot_be_cancelled(): void
    {
        $user = $this->createStaffUser('10005');

        $leave = Leave::create([
            'user_id' => $user->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(6),
            'total_days' => 2,
            'reason' => 'Testing',
            'status' => 'rejected by ho',
        ]);

        $response = $this->actingAs($user)->patch(route('leaves.cancel', $leave->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'rejected by ho',
        ]);
    }
}
