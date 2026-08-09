<?php

namespace Tests\Feature;

use App\Models\Blacklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BlacklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_blacklist_staff_and_record_appears_in_blacklist_index()
    {
        $admin = User::create([
            'id' => 'ADMIN001',
            'fullname' => 'Admin Tester',
            'role' => 'Admin',
            'station' => 'CGK',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'gender' => 'Male',
            'job_title' => 'ADMIN',
            'cluster' => 'OFFICE',
            'unit' => 'HEAD OFFICE',
            'sub_unit' => 'HEAD STATION',
            'manager' => 'DIRECTOR',
            'is_qantas' => 0,
            'join_date' => '2025-01-01',
            'salary' => 5000000,
            'is_active' => 1,
        ]);

        $staff = User::create([
            'id' => '4250491',
            'fullname' => 'ABDUL ARIFIN',
            'role' => 'Porter Bge',
            'station' => 'CGK',
            'email' => 'abdul@test.com',
            'password' => Hash::make('password123'),
            'gender' => 'Male',
            'job_title' => 'BAGGAGE HANDLING',
            'cluster' => 'GROUND HANDLING',
            'unit' => 'BAGGAGE HANDLING',
            'sub_unit' => 'PORTER MAKE-UP',
            'manager' => 'JUNAIDI',
            'is_qantas' => 0,
            'join_date' => '2025-01-01',
            'salary' => 3000000,
            'is_active' => 1,
        ]);

        $pdfFile = \Illuminate\Http\UploadedFile::fake()->create('sk_blacklist.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('blacklist.store'), [
            'user_id'         => $staff->id,
            'reason'          => 'Terbukti melakukan pelanggaran berat aset perusahaan.',
            'attachment_file' => $pdfFile,
        ]);

        $response->assertRedirect(route('blacklist.index'));

        // Assert database record in blacklists table
        $this->assertDatabaseHas('blacklists', [
            'nik' => '4250491',
            'fullname' => 'ABDUL ARIFIN',
            'station' => 'CGK',
            'banned_by' => 'Admin Tester',
        ]);

        // Assert user account was deactivated
        $this->assertDatabaseHas('users', [
            'id' => '4250491',
            'is_active' => 0,
        ]);

        // Assert record is visible on /blacklist page
        $indexResponse = $this->actingAs($admin)->get(route('blacklist.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('ABDUL ARIFIN');
        $indexResponse->assertSee('4250491');
    }

    public function test_blacklisted_user_cannot_login()
    {
        $staff = User::create([
            'id' => '999001',
            'fullname' => 'BANNED USER',
            'role' => 'Staff',
            'station' => 'CGK',
            'email' => 'banned@test.com',
            'password' => Hash::make('password123'),
            'gender' => 'Male',
            'job_title' => 'STAFF',
            'cluster' => 'GROUND HANDLING',
            'unit' => 'BAGGAGE HANDLING',
            'sub_unit' => 'PORTER',
            'manager' => 'MANAGER',
            'is_qantas' => 0,
            'join_date' => '2025-01-01',
            'salary' => 3000000,
            'is_active' => 0,
        ]);

        Blacklist::create([
            'nik' => '999001',
            'fullname' => 'BANNED USER',
            'reason' => 'Pelanggaran berat',
            'station' => 'CGK',
            'banned_by' => 'Admin Tester',
        ]);

        $response = $this->post(route('actionlogin'), [
            'login' => '999001',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertGuest();
    }
}
