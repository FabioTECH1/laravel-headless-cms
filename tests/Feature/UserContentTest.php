<?php

use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('Public Auth API', function () {
    it('allows user registration', function () {
        $response = postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'is_admin' => false,
        ]);
    });

    it('validates registration data', function () {
        postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'invalid-email',
            'password' => 'short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('allows user login', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'is_admin'],
            ]);
    });

    it('rejects invalid credentials', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('allows logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/auth/logout');

        $response->assertSuccessful();

        // Token should be revoked
        expect($user->tokens()->count())->toBe(0);
    });
});

describe('Content Ownership', function () {
    beforeEach(function () {
        // Drop table if exists to prevent errors from previous runs
        \Illuminate\Support\Facades\Schema::dropIfExists('diaries');

        // Create schema with ownership enabled
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType(
            'Diaries', // Name 'Diaries' -> slug 'diaries'
            [
                ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true]],
                ['name' => 'content', 'type' => 'longtext', 'settings' => []],
            ],
            isPublic: false,
            hasOwnership: true
        );

        $ct = \App\Models\ContentType::where('slug', 'diaries')->first();
        if (! $ct->has_ownership) {
            dump('SchemaManager failed to save has_ownership!', $ct->toArray());
        }
    });

    it('user can create owned content', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/diaries', [
            'title' => 'My First Diary',
            'content' => 'Today was a great day!',
        ]);

        $response->assertCreated();

        assertDatabaseHas('diaries', [
            'title' => 'My First Diary',
            'user_id' => $user->id,
        ]);
    });

    it('requires authentication for owned content', function () {
        postJson('/api/content/diaries', [
            'title' => 'My Diary',
            'content' => 'Content',
        ])->assertUnauthorized();
    });

    it('user cannot edit others content', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A creates content
        $response = $this->actingAs($userA, 'sanctum')->postJson('/api/content/diaries', [
            'title' => 'User A Diary',
            'content' => 'Private thoughts',
        ]);

        $diaryId = $response->json('id');

        // User B tries to edit
        $this->actingAs($userB, 'sanctum')->putJson("/api/content/diaries/{$diaryId}", [
            'title' => 'Hacked!',
        ])->assertForbidden();
    });

    it('user cannot delete others content', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A creates content
        $response = $this->actingAs($userA, 'sanctum')->postJson('/api/content/diaries', [
            'title' => 'User A Diary',
            'content' => 'Private thoughts',
        ]);

        $diaryId = $response->json('id');

        // User B tries to delete
        $this->actingAs($userB, 'sanctum')->deleteJson("/api/content/diaries/{$diaryId}")
            ->assertForbidden();
    });

    it('admin can edit any content', function () {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        // User creates content
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/diaries', [
            'title' => 'User Diary',
            'content' => 'Content',
        ]);

        $diaryId = $response->json('id');

        // Admin edits it
        $this->actingAs($admin, 'sanctum')->putJson("/api/content/diaries/{$diaryId}", [
            'title' => 'Admin Updated',
        ])->assertSuccessful();

        assertDatabaseHas('diaries', [
            'id' => $diaryId,
            'title' => 'Admin Updated',
        ]);
    });

    it('admin can delete any content', function () {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        // User creates content
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/diaries', [
            'title' => 'User Diary',
            'content' => 'Content',
        ]);

        $diaryId = $response->json('id');

        // Admin deletes it
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/content/diaries/{$diaryId}")
            ->assertNoContent();
    });

    it('user can edit their own content', function () {
        $user = User::factory()->create();

        // Create
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/diaries', [
            'title' => 'Original Title',
            'content' => 'Original Content',
        ]);

        $diaryId = $response->json('id');

        // Update
        $this->actingAs($user, 'sanctum')->putJson("/api/content/diaries/{$diaryId}", [
            'title' => 'Updated Title',
        ])->assertSuccessful();

        assertDatabaseHas('diaries', [
            'id' => $diaryId,
            'title' => 'Updated Title',
            'user_id' => $user->id,
        ]);
    });

    it('prevents user_id tampering', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Try to create content with different user_id
        $response = $this->actingAs($userA, 'sanctum')->postJson('/api/content/diaries', [
            'title' => 'My Diary',
            'content' => 'Content',
            'user_id' => $userB->id, // Attempt to spoof
        ]);

        $response->assertCreated();

        // Should be assigned to authenticated user, not spoofed user
        assertDatabaseHas('diaries', [
            'title' => 'My Diary',
            'user_id' => $userA->id,
        ]);
    });
});
