<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_admin_can_view_roles_index(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('roles.index'));

        $response->assertStatus(200);
        $response->assertSee('Hak Akses Role');
    }

    public function test_admin_can_create_custom_role(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'QC Inspector',
            'label' => 'Inspector Quality Control',
            'description' => 'Role untuk inspektor QC',
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'QC Inspector']);
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $role = Role::where('name', 'Staff')->first();
        $perm = Permission::where('name', 'assignment.view')->first();

        $response = $this->actingAs($admin)->put(route('roles.update', $role->id), [
            'label' => 'Staf Operasional Updated',
            'description' => 'Deskripsi baru',
            'permissions' => [$perm->id],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);
    }

    public function test_role_editor_renders_clean_matrix_controls(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'is_active' => true,
        ]);
        $role = Role::where('name', 'Staff')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('roles.edit', $role->id));

        $response->assertOk();
        $response->assertSee('role-access-page', false);
        $response->assertSee('id="permissionSearchInput"', false);
        $response->assertSee('id="btnSelectAll"', false);
        $response->assertSee('id="btnUnselectAll"', false);
        $response->assertSee('data-employee-filter="selected"', false);
        $response->assertSee('data-employee-filter="all"', false);
        $response->assertSee('id="employeeSearchInput"', false);
        $response->assertSee('id="floatingPermCounter"', false);
        $response->assertSee('Simpan Perubahan');
        $response->assertSee('name="permissions[]"', false);
        $response->assertSee('--ra-card-radius: var(--bs-border-radius-xl, 1rem);', false);
        $response->assertSee('--ra-card-radius: var(--bs-border-radius-lg, .75rem);', false);
    }

    public function test_user_has_permission_checks(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->assertTrue($admin->hasPermission('assignment.create'));
        $this->assertTrue($admin->canAccess('assignment', 'delete'));

        $staffRole = Role::where('name', 'Staff')->first();
        $createPerm = Permission::where('name', 'assignment.create')->first();
        $staffRole->permissions()->detach();

        $staffUser = User::factory()->create(['role' => 'Staff']);
        $this->assertFalse($staffUser->hasPermission('assignment.create'));

        $staffRole->permissions()->attach($createPerm->id);
        $staffUser = User::factory()->create(['role' => 'Staff']);
        $this->assertTrue($staffUser->hasPermission('assignment.create'));
    }

    public function test_staff_menu_visibility_is_strictly_permission_based(): void
    {
        $staff = User::factory()->create([
            'role' => 'Staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('Station Management');
        $response->assertDontSee('Role & Permissions');
        $response->assertDontSee('Overtime Approvals');
        $response->assertDontSee('Attendance Report');
    }

    public function test_admin_can_toggle_user_role_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $role = Role::where('name', 'Leader Bge')->first();
        $targetUser = User::factory()->create(['role' => 'Staff']);

        $response = $this->actingAs($admin)->postJson(route('roles.toggle-user', $role->id), [
            'user_id' => $targetUser->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $targetUser->refresh();
        $this->assertStringContainsString('Leader Bge', $targetUser->role);
    }
}
