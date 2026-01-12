<?php

namespace Tests\Feature\Api;

use App\Models\DynamicEntity;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create 'Post' content type
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Post', [
            ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true]],
            ['name' => 'views', 'type' => 'integer', 'settings' => []],
            ['name' => 'is_featured', 'type' => 'boolean', 'settings' => []],
            ['name' => 'category', 'type' => 'text', 'settings' => []],
        ], isPublic: true);

        // 2. Seed data
        $entity = (new DynamicEntity)->bind('post');
        $entity->create(['title' => 'Laravel 10', 'views' => 100, 'is_featured' => true, 'category' => 'tech', 'published_at' => now()]);
        $entity->create(['title' => 'Vue 3', 'views' => 200, 'is_featured' => false, 'category' => 'tech', 'published_at' => now()]);
        $entity->create(['title' => 'Tailwind CSS', 'views' => 150, 'is_featured' => true, 'category' => 'design', 'published_at' => now()]);
    }

    public function test_can_filter_by_equality()
    {
        $response = $this->getJson('/api/content/post?filters[category][$eq]=tech');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Laravel 10'])
            ->assertJsonFragment(['title' => 'Vue 3'])
            ->assertJsonMissing(['title' => 'Tailwind CSS']);
    }

    public function test_can_filter_by_contains()
    {
        $response = $this->getJson('/api/content/post?filters[title][$contains]=Vue');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Vue 3']);
    }

    public function test_can_filter_by_greater_than()
    {
        $response = $this->getJson('/api/content/post?filters[views][$gt]=120');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Vue 3'])        // 200
            ->assertJsonFragment(['title' => 'Tailwind CSS']) // 150
            ->assertJsonMissing(['title' => 'Laravel 10']);   // 100
    }

    public function test_can_sort_results()
    {
        // Sort by views DESC
        $response = $this->getJson('/api/content/post?sort[0]=views:desc');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('Vue 3', $data[0]['title']);         // 200
        $this->assertEquals('Tailwind CSS', $data[1]['title']); // 150
        $this->assertEquals('Laravel 10', $data[2]['title']);     // 100
    }

    public function test_can_select_specific_fields()
    {
        // Select only title
        $response = $this->getJson('/api/content/post?fields[0]=title');

        $response->assertOk();
        $item = $response->json('data.0');

        $this->assertArrayHasKey('title', $item);
        $this->assertArrayNotHasKey('views', $item);
        $this->assertArrayNotHasKey('category', $item);
        $this->assertArrayHasKey('id', $item); // ID should always be present
    }
}
