<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

describe('User Management', function () {
    it('allows admin to list users', function () {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create();

        actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 4) // Admin + 3 created = 4
            );
    });

    it('allows admin to create user', function () {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_admin' => false,
        ];

        actingAs($admin)
            ->post(route('admin.users.store'), $userData)
            ->assertRedirect(route('admin.users.index'));

        assertDatabaseHas('users', ['email' => 'new@example.com']);
    });

    it('allows admin to update user', function () {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'is_admin' => true,
        ];

        actingAs($admin)
            ->put(route('admin.users.update', $user->id), $updateData)
            ->assertRedirect();

        assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'is_admin' => true,
        ]);
    });

    it('allows admin to delete user', function () {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        actingAs($admin)
            ->delete(route('admin.users.destroy', $user->id))
            ->assertRedirect(route('admin.users.index'));

        assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('allows admin to suspend user', function () {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        actingAs($admin)
            ->put(route('admin.users.suspend', $user->id))
            ->assertRedirect();

        expect($user->fresh()->suspended_at)->not->toBeNull();
    });

    it('allows admin to unsuspend user', function () {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['suspended_at' => now()]);

        actingAs($admin)
            ->put(route('admin.users.unsuspend', $user->id))
            ->assertRedirect();

        expect($user->fresh()->suspended_at)->toBeNull();
    });

    it('prevents suspended user from logging in', function () {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'suspended_at' => now(),
            'is_admin' => true,
        ]);

        post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors(['email' => 'Your account has been suspended.']);

        assertGuest();
    });

    it('prevents admin from deleting themselves', function () {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['is_admin' => true]);

        actingAs($admin)
            ->delete(route('admin.users.destroy', $admin->id))
            ->assertRedirect();

        assertDatabaseHas('users', ['id' => $admin->id]);
    });
});
