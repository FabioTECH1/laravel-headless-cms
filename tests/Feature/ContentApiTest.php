<?php

namespace Tests\Feature;

use App\Services\SchemaManager;

describe('Content API', function () {
    it('lists content', function () {
        $schemaManager = new SchemaManager;
        $schemaManager->createType('Task', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        // Make type public so we can list without auth
        \App\Models\ContentType::where('slug', 'task')->update(['is_public' => true]);

        // Create a dummy task
        (new \App\Models\DynamicEntity)->bind('task')->create([
            'title' => 'Test Task',
            'published_at' => now(), // Needed for default list visibility
        ]);

        // URL should use singular slug as per our SchemaManager logic
        $response = $this->getJson('/api/content/task');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Test Task');
    });

    it('creates content', function () {
        $schemaManager = new SchemaManager;
        $schemaManager->createType('Task', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/content/task', [
            'title' => 'New Task',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', ['title' => 'New Task']);

        $response->assertJsonPath('data.title', 'New Task');
    });

    it('returns 404 for unknown content types', function () {
        $response = $this->getJson('/api/content/unknown-slug');

        $response->assertStatus(404);
    });

    it('deletes content', function () {
        $schemaManager = new SchemaManager;
        $schemaManager->createType('Task', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        $entity = (new \App\Models\DynamicEntity)->bind('task')->create([
            'title' => 'Task to Delete',
            'published_at' => now(), // Needed for visibility check before delete (implicit findOrFail)
        ]);

        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/content/task/{$entity->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', ['id' => $entity->id]);
    });

    it('paginates content correctly', function () {
        $schemaManager = new SchemaManager;
        $schemaManager->createType('Post', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        \App\Models\ContentType::where('slug', 'post')->update(['is_public' => true]);

        // Create 25 posts
        for ($i = 1; $i <= 25; $i++) {
            (new \App\Models\DynamicEntity)->bind('post')->create([
                'title' => "Post #$i",
                'published_at' => now(),
            ]);
        }

        // Request page 1 with 10 items
        $response = $this->getJson('/api/content/post?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total_items', 25)
            ->assertJsonCount(10, 'data');

        // Request page 3 (should have 5 items)
        $response = $this->getJson('/api/content/post?page=3&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 3)
            ->assertJsonCount(5, 'data');
    });
});
