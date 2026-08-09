<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JobTitle;
use App\Models\Unit;
use App\Models\SubUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_job_titles_page()
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('master_data.job_titles.index'));
        $response->assertStatus(200);
        $response->assertSee('Job Titles');
    }

    public function test_admin_can_view_create_and_edit_job_title_pages()
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);
        $jobTitle = JobTitle::first();

        $response = $this->actingAs($admin)->get(route('master_data.job_titles.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get(route('master_data.job_titles.edit', $jobTitle->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_units_page()
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('master_data.units.index'));
        $response->assertStatus(200);
        $response->assertSee('Units');
    }

    public function test_admin_can_view_sub_units_page()
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('master_data.sub_units.index'));
        $response->assertStatus(200);
        $response->assertSee('Sub Units');
    }

    public function test_admin_can_create_update_delete_job_title()
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);

        // Create
        $response = $this->actingAs($admin)->post(route('master_data.job_titles.store'), [
            'name' => 'SUPERVISOR OPERATIONAL',
            'is_active' => 1,
        ]);
        $response->assertRedirect(route('master_data.job_titles.index'));
        $this->assertDatabaseHas('job_titles', ['name' => 'SUPERVISOR OPERATIONAL']);

        // Update
        $jobTitle = JobTitle::where('name', 'SUPERVISOR OPERATIONAL')->first();
        $response = $this->actingAs($admin)->put(route('master_data.job_titles.update', $jobTitle->id), [
            'name' => 'SUPERVISOR OPERATIONAL UPDATED',
            'is_active' => 1,
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('master_data.job_titles.index'));
        $this->assertDatabaseHas('job_titles', ['name' => 'SUPERVISOR OPERATIONAL UPDATED']);

        // Delete
        $response = $this->actingAs($admin)->delete(route('master_data.job_titles.destroy', $jobTitle->id));
        $response->assertRedirect(route('master_data.job_titles.index'));
        $this->assertDatabaseMissing('job_titles', ['id' => $jobTitle->id]);
    }

    public function test_admin_can_create_update_delete_unit()
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);

        // Create
        $response = $this->actingAs($admin)->post(route('master_data.units.store'), [
            'name' => 'SAFETY UNIT',
            'is_active' => 1,
        ]);
        $response->assertRedirect(route('master_data.units.index'));
        $this->assertDatabaseHas('units', ['name' => 'SAFETY UNIT']);

        // Delete
        $unit = Unit::where('name', 'SAFETY UNIT')->first();
        $response = $this->actingAs($admin)->delete(route('master_data.units.destroy', $unit->id));
        $response->assertRedirect(route('master_data.units.index'));
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_admin_can_create_update_delete_sub_unit()
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);

        // Create
        $response = $this->actingAs($admin)->post(route('master_data.sub_units.store'), [
            'name' => 'MARSHALLING TEAM',
            'is_active' => 1,
        ]);
        $response->assertRedirect(route('master_data.sub_units.index'));
        $this->assertDatabaseHas('sub_units', ['name' => 'MARSHALLING TEAM']);

        // Delete
        $subUnit = SubUnit::where('name', 'MARSHALLING TEAM')->first();
        $response = $this->actingAs($admin)->delete(route('master_data.sub_units.destroy', $subUnit->id));
        $response->assertRedirect(route('master_data.sub_units.index'));
        $this->assertDatabaseMissing('sub_units', ['id' => $subUnit->id]);
    }

    public function test_user_foreign_keys_are_populated_and_linked()
    {
        $jt = JobTitle::first();
        $u = Unit::first();
        $su = SubUnit::first();

        $user = User::factory()->create([
            'role' => 'Staff',
            'job_title' => $jt->name,
            'unit' => $u->name,
            'sub_unit' => $su->name,
        ]);

        $this->assertEquals($jt->id, $user->job_title_id);
        $this->assertEquals($u->id, $user->unit_id);
        $this->assertEquals($su->id, $user->sub_unit_id);

        $this->assertEquals($jt->name, $user->jobTitle->name);
        $this->assertEquals($u->name, $user->unitRelation->name);
        $this->assertEquals($su->name, $user->subUnitRelation->name);
    }
}
