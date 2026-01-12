<?php

namespace Tests\Feature;

use App\Models\DynamicEntity;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_single_type_via_schema_manager()
    {
        $schemaManager = app(SchemaManager::class);
        $type = $schemaManager->createType('Homepage', [
            ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true]],
        ], isPublic: true, isSingle: true);

        $this->assertTrue($type->is_single);
        $this->assertDatabaseHas('content_types', [
            'slug' => 'homepage',
            'is_single' => true,
        ]);
    }

    public function test_api_returns_object_for_single_type()
    {
        // 1. Create Single Type
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Homepage', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isSingle: true);

        // 2. Add data
        $entity = (new DynamicEntity)->bind('homepage');
        $entity->create(['title' => 'Welcome Home', 'published_at' => now()]);

        // 3. Request API
        $response = $this->getJson('/api/content/homepage');

        $response->assertOk()
            ->assertJsonPath('data.title', 'Welcome Home');

        // Verify structure is NOT an array of items, but a single object resource
        // Typically resources wrap in data.
        // Array collection: data => [ { id: ... }, { id: ... } ]
        // Single resource: data => { id: ... }
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        // It should NOT be a numbered array of items
        $this->assertArrayNotHasKey(0, $data);
    }

    public function test_api_returns_null_data_if_single_type_empty()
    {
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Settings', [
            ['name' => 'site_name', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isSingle: true);

        $response = $this->getJson('/api/content/settings');

        $response->assertOk()
            ->assertJson(['data' => null]);
    }
}
