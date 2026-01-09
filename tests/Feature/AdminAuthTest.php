<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin login screen can be rendered', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});

test('admins can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard'));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('non-admins cannot access admin dashboard', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});
