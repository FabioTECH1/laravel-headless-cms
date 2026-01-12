<?php

namespace Tests\Feature;

use App\Models\DynamicEntity;
use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_use_components()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $manager = app(SchemaManager::class);

        // 1. Create a Component Type "SEO"
        $seoType = $manager->createType('SEO', [
            [
                'name' => 'meta_title',
                'type' => 'text',
                'settings' => ['required' => true],
            ],
            [
                'name' => 'meta_description',
                'type' => 'longtext',
                'settings' => [],
            ],
        ], false, false, true);

        // 2. Create another Component "Hero"
        $heroType = $manager->createType('Hero', [
            [
                'name' => 'headline',
                'type' => 'text',
                'settings' => ['required' => true],
            ],
        ], false, false, true);

        // 3. Create a Content Type "Page" using these components
        $manager->createType('Page', [
            [
                'name' => 'title',
                'type' => 'text',
                'settings' => ['required' => true],
            ],
            [
                'name' => 'seo_data',
                'type' => 'component',
                'settings' => [
                    'related_content_type_id' => $seoType->id,
                    'required' => true,
                ],
            ],
            [
                'name' => 'sections',
                'type' => 'dynamic_zone',
                'settings' => [
                    'allowed_component_ids' => [$heroType->id],
                ],
            ],
        ], false, false, false);

        // 4. Test Storing Content with Components
        $payload = [
            'title' => 'Home Page',
            'seo_data' => [
                'meta_title' => 'Home SEO',
                'meta_description' => 'Best page ever',
            ],
            'sections' => [
                [
                    '__component' => 'hero', // Must match slug
                    'headline' => 'Welcome Hero',
                ],
            ],
        ];

        $response = $this->post(route('admin.content.store', 'page'), $payload);

        $response->assertRedirect(route('admin.content.index', 'page'));
        $response->assertSessionHas('success');

        // 5. Verify Database
        $this->assertDatabaseHas('pages', [
            'title' => 'Home Page',
        ]);

        $page = (new DynamicEntity)->bind('page')->first();

        $this->assertIsArray($page->seo_data);
        $this->assertEquals('Home SEO', $page->seo_data['meta_title']);

        $this->assertIsArray($page->sections);
        $this->assertCount(1, $page->sections);
        $this->assertEquals('hero', $page->sections[0]['__component']);
        $this->assertEquals('Welcome Hero', $page->sections[0]['headline']);
    }

    public function test_validates_component_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $manager = app(SchemaManager::class);

        // 1. Create a Component Type "SEO"
        $seoType = $manager->createType('SEO', [
            [
                'name' => 'meta_title',
                'type' => 'text',
                'settings' => ['required' => true],
            ],
        ], false, false, true);

        // 2. Create Content Type "Page"
        $manager->createType('Page', [
            [
                'name' => 'title',
                'type' => 'text',
                'settings' => ['required' => true],
            ],
            [
                'name' => 'seo_data',
                'type' => 'component',
                'settings' => [
                    'related_content_type_id' => $seoType->id,
                ],
            ],
        ], false, false, false);

        // 3. Send Invalid Data (missing required meta_title in component)
        $payload = [
            'title' => 'Test Page',
            'seo_data' => [
                'meta_description' => 'Missing title',
            ],
        ];

        $response = $this->post(route('admin.content.store', 'page'), $payload);

        $response->assertSessionHasErrors(['seo_data']);
    }
}
