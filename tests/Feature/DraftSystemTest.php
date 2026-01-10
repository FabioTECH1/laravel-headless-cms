<?php

namespace Tests\Feature;

use App\Services\SchemaManager;
use Illuminate\Support\Facades\Schema;

describe('Draft System', function () {
    it('creates tables with published_at column', function () {
        $schemaManager = new SchemaManager;
        $schemaManager->createType('Post', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        expect(Schema::hasColumn('posts', 'published_at'))->toBeTrue();
    });

    it('filters unpublished content by default', function () {
        $schemaManager = new SchemaManager;
        $schemaManager->createType('Post', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        \App\Models\ContentType::where('slug', 'post')->update(['is_public' => true]);

        $entity = (new \App\Models\DynamicEntity)->bind('post');

        // Published Post
        $entity->create([
            'title' => 'Published Post',
            'published_at' => now()->subDay(),
        ]);

        // Draft Post
        $entity->create([
            'title' => 'Draft Post',
            'published_at' => null,
        ]);

        // Future Post (Scheduled)
        $entity->create([
            'title' => 'Future Post',
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/content/post');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Published Post'])
            ->assertJsonMissing(['title' => 'Draft Post'])
            ->assertJsonMissing(['title' => 'Future Post']);
    });

    it('allows viewing drafts via status parameter', function () {
        $schemaManager = new SchemaManager;
        $schemaManager->createType('Post', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        $user = \App\Models\User::factory()->create();

        $entity = (new \App\Models\DynamicEntity)->bind('post');

        $entity->create([
            'title' => 'Draft Post',
            'published_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/content/post?status=draft');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Draft Post']);
    });
});
