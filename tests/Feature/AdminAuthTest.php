<?php

namespace Tests\Feature;

use App\Models\User;

describe('Admin Authentication', function () {
    it('renders the login screen', function () {
        User::factory()->create();

        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    });

    it('redirects to registration if no users exist', function () {
        $response = $this->get('/admin/login');

        $response->assertRedirect(route('admin.register'));
    });

    it('allows admins to authenticate', function () {
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

    it('prevents authentication with invalid password', function () {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    });

    it('prevents non-admins from accessing dashboard', function () {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    });
});
