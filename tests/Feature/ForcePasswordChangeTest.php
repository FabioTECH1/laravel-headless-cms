<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_user_must_change_password_redirect(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.password.expired'));
    }

    public function test_user_can_see_password_expired_page(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.password.expired'));

        $response->assertStatus(200);
    }

    public function test_user_can_update_expired_password(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'is_admin' => true,
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->put(route('admin.password.expired.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_user_without_flag_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}
