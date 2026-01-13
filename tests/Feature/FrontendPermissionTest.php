<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    // Seed roles and permissions for testing
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

it('shares user permissions and roles with frontend via inertia', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('auth.permissions')
        ->has('auth.roles')
    );

    // Verify it's an array
    $props = $response->viewData('page')['props'];
    expect($props['auth']['permissions'])->toBeArray();
    expect($props['auth']['roles'])->toBeArray();
    expect($props['auth']['roles'])->toContain('editor');
});

it('super admin has all permissions in frontend', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $props = $response->viewData('page')['props'];

    expect($props['auth']['roles'])->toContain('super-admin');
    expect(count($props['auth']['permissions']))->toBeGreaterThan(10);
});

it('editor has limited permissions in frontend', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $permissions = $response->viewData('page')['props']['auth']['permissions'];

    // Editor should have content permissions
    expect($permissions)->toContain('view-content');
    expect($permissions)->toContain('create-content');
    expect($permissions)->toContain('edit-content');

    // But not user management
    expect($permissions)->not->toContain('create-users');
    expect($permissions)->not->toContain('delete-users');
});

it('viewer has read-only permissions in frontend', function () {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $response = $this->actingAs($user)->get('/admin/dashboard');

    $permissions = $response->viewData('page')['props']['auth']['permissions'];

    // Viewer should only have view permissions
    expect($permissions)->toContain('view-content');
    expect($permissions)->toContain('view-media');

    // But no create/edit/delete
    expect($permissions)->not->toContain('create-content');
    expect($permissions)->not->toContain('edit-content');
    expect($permissions)->not->toContain('delete-content');
});

it('allows authorized users to access protected routes', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $response = $this->actingAs($user)->get('/admin/schema');
    $response->assertSuccessful();
});
