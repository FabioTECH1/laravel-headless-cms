<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run specific seeders needed for roles/permissions
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_first_registered_user_becomes_super_admin()
    {
        // Ensure no users exist initially
        $this->assertEquals(0, User::count());

        $response = $this->post(route('admin.register.store'), [
            'name' => 'First Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $this->assertEquals(1, User::count());

        $user = User::first();

        // Assert basic admin flag
        $this->assertTrue($user->is_admin);

        // Assert Role Assignment
        // Note: The seeder creates 'super-admin' with guard 'web'
        $this->assertTrue($user->hasRole('super-admin'), 'User does not have super-admin role');

        // Double check specific permission via role
        $this->assertTrue($user->can('view-content'), 'User cannot view content (missing permission via role)');
    }

    public function test_subsequent_users_cannot_register_via_this_route()
    {
        // Create the first user
        User::factory()->create(['is_admin' => true]);

        $response = $this->post(route('admin.register.store'), [
            'name' => 'Second Admin',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Should redirect back to login or dashboard with error,
        // Controller logic: return redirect()->route('admin.login')->with('error', ...);
        $response->assertRedirect(route('admin.login'));

        $this->assertEquals(1, User::count());
    }
}
