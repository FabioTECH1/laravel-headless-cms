<?php

namespace Tests\Feature\Admin;

use App\Models\DynamicEntity;
use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_redirects_to_create_if_no_entry()
    {
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Homepage', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isSingle: true);

        $response = $this->actingAs($this->user)
            ->get(route('admin.content.index', 'homepage'));

        $response->assertRedirect(route('admin.content.create', 'homepage'));
    }

    public function test_index_redirects_to_edit_if_entry_exists()
    {
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Homepage', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isSingle: true);

        $entity = (new DynamicEntity)->bind('homepage');
        $item = $entity->create(['title' => 'Home']);

        $response = $this->actingAs($this->user)
            ->get(route('admin.content.index', 'homepage'));

        $response->assertRedirect(route('admin.content.edit', ['slug' => 'homepage', 'id' => $item->id]));
    }

    public function test_cannot_create_duplicate_single_type()
    {
        $schemaManager = app(SchemaManager::class);
        $schemaManager->createType('Homepage', [
            ['name' => 'title', 'type' => 'text', 'settings' => []],
        ], isPublic: true, isSingle: true);

        $entity = (new DynamicEntity)->bind('homepage');
        $entity->create(['title' => 'Home']);

        $response = $this->actingAs($this->user)
            ->post(route('admin.content.store', 'homepage'), [
                'title' => 'Another Home',
            ]);

        $response->assertStatus(400); // We used abort(400)
    }
}
