<?php

namespace Tests\Feature;

use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it can list content', function () {
    $schemaManager = new SchemaManager;
    $schemaManager->createType('Task', [
        ['name' => 'title', 'type' => 'text'],
    ]);

    // Create a dummy task
    (new \App\Models\DynamicEntity)->bind('task')->create([
        'title' => 'Test Task',
        'published_at' => now(), // Needed for default list visibility
    ]);

    // URL should use singular slug as per our SchemaManager logic
    $response = $this->getJson('/api/content/task');

    $response->assertStatus(200)
        ->assertJsonFragment(['title' => 'Test Task']);
});

test('it can create content', function () {
    $schemaManager = new SchemaManager;
    $schemaManager->createType('Task', [
        ['name' => 'title', 'type' => 'text'],
    ]);

    $response = $this->postJson('/api/content/task', [
        'title' => 'New Task',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('tasks', ['title' => 'New Task']);
});

test('it handles 404 for unknown types', function () {
    $response = $this->getJson('/api/content/unknown-slug');

    $response->assertStatus(404);
});

test('it can delete content', function () {
    $schemaManager = new SchemaManager;
    $schemaManager->createType('Task', [
        ['name' => 'title', 'type' => 'text'],
    ]);

    $entity = (new \App\Models\DynamicEntity)->bind('task')->create([
        'title' => 'Task to Delete',
        'published_at' => now(), // Needed for visibility check before delete (implicit findOrFail)
    ]);

    $response = $this->deleteJson("/api/content/task/{$entity->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('tasks', ['id' => $entity->id]);
});
