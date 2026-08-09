<?php

namespace Tests\Feature;

use App\Http\Controllers\FaceSampleController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceSampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_access_user_face_samples_page()
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $targetUser = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($admin)->get(route('users.face-samples.index', $targetUser->id));

        $response->assertStatus(200);
        $response->assertSee('Foto Referensi Wajah');
        $response->assertSee($targetUser->fullname);
    }

    public function test_non_admin_cannot_access_face_samples_page()
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $targetUser = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('users.face-samples.index', $targetUser->id));

        $response->assertStatus(403);
    }

    public function test_admin_can_upload_3_position_face_samples()
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $targetUser = User::factory()->create(['role' => 'staff']);

        $dummyBase64 = 'data:image/jpeg;base64,' . base64_encode('fake-image-content');

        // Upload Front
        $resFront = $this->actingAs($admin)->post(route('users.face-samples.store', $targetUser->id), [
            'position' => 'front',
            'photo'    => $dummyBase64,
        ]);
        $resFront->assertRedirect(route('users.face-samples.index', $targetUser->id));
        Storage::disk('public')->assertExists(FaceSampleController::userDir($targetUser->id) . '/front.jpg');

        $this->assertFalse(FaceSampleController::isComplete($targetUser->id));

        // Upload Right
        $this->actingAs($admin)->post(route('users.face-samples.store', $targetUser->id), [
            'position' => 'right',
            'photo'    => $dummyBase64,
        ]);
        Storage::disk('public')->assertExists(FaceSampleController::userDir($targetUser->id) . '/right.jpg');

        // Upload Left
        $this->actingAs($admin)->post(route('users.face-samples.store', $targetUser->id), [
            'position' => 'left',
            'photo'    => $dummyBase64,
        ]);
        Storage::disk('public')->assertExists(FaceSampleController::userDir($targetUser->id) . '/left.jpg');

        $this->assertTrue(FaceSampleController::isComplete($targetUser->id));
        $this->assertNotNull($targetUser->fresh()->face_registered_at);
    }

    public function test_user_can_fetch_face_samples_api()
    {
        $user = User::factory()->create(['role' => 'staff']);

        $dummyBase64 = 'data:image/jpeg;base64,' . base64_encode('fake-image-content');
        $admin = User::factory()->create(['role' => 'Admin']);

        foreach (['front', 'right', 'left'] as $pos) {
            $this->actingAs($admin)->post(route('users.face-samples.store', $user->id), [
                'position' => $pos,
                'photo'    => $dummyBase64,
            ]);
        }

        $response = $this->actingAs($user)->get(route('attendance.face-samples.api'));

        $response->assertStatus(200);
        $response->assertJson([
            'user_id' => $user->id,
            'is_complete' => true,
        ]);
    }

    public function test_admin_can_delete_user_face_samples()
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $targetUser = User::factory()->create(['role' => 'staff']);

        $dummyBase64 = 'data:image/jpeg;base64,' . base64_encode('fake-image-content');
        $this->actingAs($admin)->post(route('users.face-samples.store', $targetUser->id), [
            'position' => 'front',
            'photo'    => $dummyBase64,
        ]);

        $response = $this->actingAs($admin)->delete(route('users.face-samples.destroy', $targetUser->id));
        $response->assertRedirect(route('users.face-samples.index', $targetUser->id));

        Storage::disk('public')->assertMissing(FaceSampleController::userDir($targetUser->id) . '/front.jpg');
        $this->assertNull($targetUser->fresh()->face_registered_at);
    }
}
