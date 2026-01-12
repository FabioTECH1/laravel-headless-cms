<?php

namespace Tests\Feature;

use App\Models\DynamicEntity;
use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_admin' => true]);
    }

    public function test_can_create_localized_content_type()
    {
        $schemaManager = app(SchemaManager::class);
        $type = $schemaManager->createType('Article', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isLocalized: true);

        $this->assertTrue($type->is_localized);
        $this->assertTrue(\Schema::hasColumn('articles', 'locale'));
    }

    public function test_can_create_localized_entries()
    {
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Article', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isLocalized: true);

        $entity = (new DynamicEntity)->bind('article');

        // Create English entry
        $en = $entity->create([
            'title' => 'Hello World',
            'locale' => 'en',
        ]);

        // Create French entry
        $fr = $entity->create([
            'title' => 'Bonjour le Monde',
            'locale' => 'fr',
        ]);

        $this->assertEquals('en', $en->locale);
        $this->assertEquals('fr', $fr->locale);
    }

    public function test_api_filters_by_locale()
    {
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Article', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isLocalized: true);

        $entity = (new DynamicEntity)->bind('article');

        // Create English entry
        $entity->create([
            'title' => 'Hello World',
            'locale' => 'en',
            'published_at' => now(),
        ]);

        // Create French entry
        $entity->create([
            'title' => 'Bonjour le Monde',
            'locale' => 'fr',
            'published_at' => now(),
        ]);

        // Test English default
        $response = $this->getJson('/api/content/article');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Hello World');

        // Test Explicit English
        $response = $this->getJson('/api/content/article?locale=en');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Hello World');

        // Test French
        $response = $this->getJson('/api/content/article?locale=fr');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Bonjour le Monde');
    }

    public function test_admin_store_saves_locale()
    {
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Article', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isLocalized: true);

        $response = $this->actingAs($this->user)
            ->post(route('admin.content.store', 'article'), [
                'title' => 'Hola Mundo',
                'locale' => 'es',
            ]);

        $response->assertRedirect();

        $entity = (new DynamicEntity)->bind('article');
        $this->assertDatabaseHas($entity->getTable(), [
            'title' => 'Hola Mundo',
            'locale' => 'es',
        ]);
    }
}
