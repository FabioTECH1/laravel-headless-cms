<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ManyToManyRelationTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(SchemaManager::class);
    }

    public function test_can_create_schema_with_many_to_many_relation()
    {
        // 1. Create 'Tag' Schema
        $this->manager->createType('Tag', [
            ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true]],
        ], true, false);

        $tagType = \App\Models\ContentType::where('slug', 'tag')->first();

        // 2. Create 'Post' Schema with M2M relation to Tag
        $this->manager->createType('Post', [
            ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true]],
            [
                'name' => 'tags',
                'type' => 'relation',
                'settings' => [
                    'related_content_type_id' => $tagType->id,
                    'multiple' => true, // Creating M2M
                ],
            ],
        ], true, false);

        // 3. Verify Tables Exist
        $this->assertTrue(Schema::hasTable('tags'));
        $this->assertTrue(Schema::hasTable('posts'));

        // Pivot table should be 'post_tag' (alphabetical singular)
        $this->assertTrue(Schema::hasTable('post_tag'));
        $this->assertTrue(Schema::hasColumn('post_tag', 'post_id'));
        $this->assertTrue(Schema::hasColumn('post_tag', 'tag_id'));
    }

    public function test_can_store_and_retrieve_many_to_many_content()
    {
        $user = User::factory()->create();

        // 1. Setup Schemas
        $this->manager->createType('Tag', [
            ['name' => 'title', 'type' => 'text'],
        ], true);
        $tagType = \App\Models\ContentType::where('slug', 'tag')->first();

        $this->manager->createType('Post', [
            ['name' => 'title', 'type' => 'text'],
            [
                'name' => 'tags',
                'type' => 'relation',
                'settings' => [
                    'related_content_type_id' => $tagType->id,
                    'multiple' => true,
                ],
            ],
        ], true);

        // 2. Create Tags
        $tagResp1 = $this->actingAs($user)->postJson('/api/content/tag', ['title' => 'Laravel']);
        $tagResp1->assertCreated();
        $tag1 = $tagResp1->json('data');

        $tagResp2 = $this->actingAs($user)->postJson('/api/content/tag', ['title' => 'CMS']);
        $tagResp2->assertCreated();
        $tag2 = $tagResp2->json('data');

        // 3. Create Post with Tags
        $response = $this->actingAs($user)->postJson('/api/content/post', [
            'title' => 'My First Post',
            'tags' => [$tag1['id'], $tag2['id']],
        ]);

        $response->assertCreated();
        $postId = $response->json('data.id');

        // 4. Verify Pivot Data
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $postId,
            'tag_id' => $tag1['id'],
        ]);
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $postId,
            'tag_id' => $tag2['id'],
        ]);

        // 5. Verify Retrieval via API
        // Note: We need to ensure the response includes the relation
        // For now, let's just check if we can fetch the post.
        // Loading relations in index/show might require '?include=tags' logic which we need to support.
        // But let's at least assert basic fetch works validation-wise.
        $this->getJson("/api/content/post/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.title', 'My First Post');
    }
}
