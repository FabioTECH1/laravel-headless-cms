<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);
});

describe('Settings Index', function () {
    it('displays settings page for authenticated user', function () {
        actingAs($this->user)
            ->get(route('admin.settings.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->has('tokens')
                ->has('systemInfo')
                ->has('systemInfo.laravel_version')
                ->has('systemInfo.environment')
                ->has('systemInfo.debug_mode')
            );
    });
});

describe('Profile Update', function () {
    it('updates user name successfully', function () {
        actingAs($this->user)
            ->put(route('admin.settings.profile.update'), [
                'name' => 'Updated Name',
                'email' => $this->user->email,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully.');

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => $this->user->email,
        ]);
    });

    it('updates user email successfully', function () {
        actingAs($this->user)
            ->put(route('admin.settings.profile.update'), [
                'name' => $this->user->name,
                'email' => 'newemail@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'newemail@example.com',
        ]);
    });

    it('validates required name field', function () {
        actingAs($this->user)
            ->put(route('admin.settings.profile.update'), [
                'name' => '',
                'email' => $this->user->email,
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('validates required email field', function () {
        actingAs($this->user)
            ->put(route('admin.settings.profile.update'), [
                'name' => $this->user->name,
                'email' => '',
            ])
            ->assertSessionHasErrors(['email']);
    });

    it('validates email format', function () {
        actingAs($this->user)
            ->put(route('admin.settings.profile.update'), [
                'name' => $this->user->name,
                'email' => 'invalid-email',
            ])
            ->assertSessionHasErrors(['email']);
    });

    it('validates unique email', function () {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        actingAs($this->user)
            ->put(route('admin.settings.profile.update'), [
                'name' => $this->user->name,
                'email' => 'other@example.com',
            ])
            ->assertSessionHasErrors(['email']);
    });

    it('allows keeping the same email', function () {
        actingAs($this->user)
            ->put(route('admin.settings.profile.update'), [
                'name' => 'New Name',
                'email' => $this->user->email,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    });
});

describe('Password Update', function () {
    it('updates password successfully', function () {
        actingAs($this->user)
            ->put(route('admin.settings.password.update'), [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->user->refresh();
        expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
    });

    it('validates current password', function () {
        actingAs($this->user)
            ->put(route('admin.settings.password.update'), [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertSessionHasErrors(['current_password']);
    });

    it('validates password confirmation', function () {
        actingAs($this->user)
            ->put(route('admin.settings.password.update'), [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
            ])
            ->assertSessionHasErrors(['password']);
    });

    it('validates minimum password length', function () {
        actingAs($this->user)
            ->put(route('admin.settings.password.update'), [
                'current_password' => 'password123',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors(['password']);
    });

    it('requires current password', function () {
        actingAs($this->user)
            ->put(route('admin.settings.password.update'), [
                'current_password' => '',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertSessionHasErrors(['current_password']);
    });

    it('requires new password', function () {
        actingAs($this->user)
            ->put(route('admin.settings.password.update'), [
                'current_password' => 'password123',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasErrors(['password']);
    });
});

describe('System Info', function () {
    it('returns correct system information', function () {
        actingAs($this->user)
            ->get(route('admin.settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('systemInfo.laravel_version', app()->version())
                ->where('systemInfo.environment', app()->environment())
                ->where('systemInfo.debug_mode', config('app.debug'))
            );
    });
});
