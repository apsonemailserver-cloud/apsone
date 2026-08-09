<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_only_sees_own_certificates()
    {
        $staff1 = User::factory()->create(['role' => 'staff']);
        $staff2 = User::factory()->create(['role' => 'staff']);

        $cert1 = Certificate::create([
            'user_id'          => $staff1->id,
            'certificate_name' => 'Sertifikat Staff 1',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYear()->toDateString(),
            'status'           => 'Approved',
        ]);

        $cert2 = Certificate::create([
            'user_id'          => $staff2->id,
            'certificate_name' => 'Sertifikat Staff 2',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYear()->toDateString(),
            'status'           => 'Approved',
        ]);

        $response = $this->actingAs($staff1)->get(route('admin.training.certificates.index'));

        $response->assertStatus(200);
        $response->assertSee('Sertifikat Staff 1');
        $response->assertDontSee('Sertifikat Staff 2');
    }

    public function test_admin_sees_all_certificates()
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $staff1 = User::factory()->create(['role' => 'staff']);
        $staff2 = User::factory()->create(['role' => 'staff']);

        Certificate::create([
            'user_id'          => $staff1->id,
            'certificate_name' => 'Sertifikat Staff 1',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYear()->toDateString(),
            'status'           => 'Approved',
        ]);

        Certificate::create([
            'user_id'          => $staff2->id,
            'certificate_name' => 'Sertifikat Staff 2',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYear()->toDateString(),
            'status'           => 'Approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.training.certificates.index'));

        $response->assertStatus(200);
        $response->assertSee('Sertifikat Staff 1');
        $response->assertSee('Sertifikat Staff 2');
    }

    public function test_non_admin_cannot_edit_other_user_certificate()
    {
        $staff1 = User::factory()->create(['role' => 'staff']);
        $staff2 = User::factory()->create(['role' => 'staff']);

        $cert2 = Certificate::create([
            'user_id'          => $staff2->id,
            'certificate_name' => 'Sertifikat Staff 2',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYear()->toDateString(),
            'status'           => 'Approved',
        ]);

        $response = $this->actingAs($staff1)->get(route('admin.training.certificates.edit', $cert2->id));
        $response->assertStatus(403);

        $responseDelete = $this->actingAs($staff1)->delete(route('admin.training.certificates.destroy', $cert2->id));
        $responseDelete->assertStatus(403);
    }
}
