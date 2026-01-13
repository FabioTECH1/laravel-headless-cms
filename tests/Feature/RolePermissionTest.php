<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run permission and role seeders
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_user_can_be_assigned_role()
    {
        $user = User::factory()->create();
        $role = Role::findByName('editor');

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_user_has_permissions_through_role()
    {
        $user = User::factory()->create();
        $user->assignRole('editor');

        $this->assertTrue($user->hasPermissionTo('edit-content'));
        $this->assertTrue($user->hasPermissionTo('view-media'));
        $this->assertFalse($user->hasPermissionTo('delete-users'));
    }

    public function test_super_admin_has_all_permissions()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->assignRole('super-admin');

        $this->assertTrue($user->hasPermissionTo('delete-users'));
        $this->assertTrue($user->hasPermissionTo('delete-schema'));
        $this->assertTrue($user->hasPermissionTo('manage-api-tokens'));
    }

    public function test_middleware_blocks_unauthorized_access()
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $response = $this->actingAs($viewer)->delete('/admin/content/blog-post/1');

        $response->assertForbidden();
    }

    public function test_middleware_allows_authorized_access()
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)->get('/admin/schema');

        $response->assertSuccessful();
    }

    public function test_only_super_admin_can_access_roles()
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)->get('/admin/roles');

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_roles()
    {
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)->get('/admin/roles');

        $response->assertSuccessful();
    }

    public function test_can_create_role_with_permissions()
    {
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)->post('/admin/roles', [
            'name' => 'content-manager',
            'permissions' => ['view-content', 'create-content', 'edit-content'],
        ]);

        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('roles', ['name' => 'content-manager']);

        $role = Role::findByName('content-manager');
        $this->assertTrue($role->hasPermissionTo('edit-content'));
    }

    public function test_cannot_delete_role_with_users()
    {
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->assignRole('super-admin');

        $user = User::factory()->create();
        $user->assignRole('editor');

        $editorRole = Role::findByName('editor');

        $response = $this->actingAs($superAdmin)->delete("/admin/roles/{$editorRole->id}");

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('roles', ['name' => 'editor']);
    }
}
