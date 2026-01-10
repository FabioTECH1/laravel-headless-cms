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
});
