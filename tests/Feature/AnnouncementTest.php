<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_announcement_with_target_stations(): void
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

        $response = $this->actingAs($admin)->post(route('announcements.store'), [
            'title' => 'Pengumuman Penting CGK & SUB',
            'content' => 'Harap segera melakukan perpanjangan PAS Bandara.',
            'target_stations' => ['CGK', 'SUB'],
        ]);

        $response->assertRedirect(route('announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'Pengumuman Penting CGK & SUB',
            'created_by' => 'ADMIN01',
        ]);

        $announcement = Announcement::first();
        $this->assertEquals(['CGK', 'SUB'], $announcement->target_stations);
    }

    public function test_user_only_sees_targeted_announcements(): void
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

        $cgkUser = User::create([
            'id' => 'USER_CGK',
            'fullname' => 'CGK User',
            'email' => 'cgk@test.com',
            'password' => bcrypt('password'),
            'role' => 'Staff',
            'station' => 'CGK',
            'gender' => 'Male',
            'is_active' => true,
            'join_date' => now()->toDateString(),
        ]);

        $subUser = User::create([
            'id' => 'USER_SUB',
            'fullname' => 'SUB User',
            'email' => 'sub@test.com',
            'password' => bcrypt('password'),
            'role' => 'Staff',
            'station' => 'SUB',
            'gender' => 'Male',
            'is_active' => true,
            'join_date' => now()->toDateString(),
        ]);

        // CGK only announcement
        Announcement::create([
            'title' => 'Khusus CGK',
            'content' => 'Pengumuman area CGK',
            'target_stations' => ['CGK'],
            'created_by' => $admin->id,
        ]);

        // SUB only announcement
        Announcement::create([
            'title' => 'Khusus SUB',
            'content' => 'Pengumuman area SUB',
            'target_stations' => ['SUB'],
            'created_by' => $admin->id,
        ]);

        // All stations announcement
        Announcement::create([
            'title' => 'Semua Station',
            'content' => 'Pengumuman nasional',
            'target_stations' => ['ALL'],
            'created_by' => $admin->id,
        ]);

        $cgkResponse = $this->actingAs($cgkUser)->get(route('announcements.index'));
        $cgkResponse->assertStatus(200);
        $cgkResponse->assertSee('Khusus CGK');
        $cgkResponse->assertSee('Semua Station');
        $cgkResponse->assertDontSee('Khusus SUB');

        $subResponse = $this->actingAs($subUser)->get(route('announcements.index'));
        $subResponse->assertStatus(200);
        $subResponse->assertSee('Khusus SUB');
        $subResponse->assertSee('Semua Station');
        $subResponse->assertDontSee('Khusus CGK');
    }

    public function test_marking_announcement_as_read_decreases_unread_count(): void
    {
        $user = User::create([
            'id' => 'USER01',
            'fullname' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'Staff',
            'station' => 'CGK',
            'gender' => 'Male',
            'is_active' => true,
            'join_date' => now()->toDateString(),
        ]);

        $announcement = Announcement::create([
            'title' => 'Info Perpanjangan Kontrak',
            'content' => 'Pengumuman tanggal 1-20.',
            'target_stations' => ['ALL'],
            'created_by' => $user->id,
        ]);

        $this->assertFalse($announcement->isReadBy($user));

        $response = $this->actingAs($user)->post(route('announcements.read', $announcement->id));
        $this->assertTrue($announcement->fresh()->isReadBy($user));
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_access_create_and_edit_announcement_pages(): void
    {
        $admin = User::create([
            'id' => 'ADMIN02',
            'fullname' => 'Admin Two',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'station' => 'CGK',
            'gender' => 'Male',
            'is_active' => true,
            'join_date' => now()->toDateString(),
        ]);

        $createResponse = $this->actingAs($admin)->get(route('announcements.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Tambah Pengumuman');

        $announcement = Announcement::create([
            'title' => 'Pengumuman Uji',
            'content' => 'Isi Uji',
            'target_stations' => ['ALL'],
            'created_by' => $admin->id,
        ]);

        $editResponse = $this->actingAs($admin)->get(route('announcements.edit', $announcement->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Pengumuman');
    }
}
