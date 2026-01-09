<?php

namespace Tests\Feature;

use App\Models\DynamicEntity;
use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $schemaManager;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->schemaManager = new SchemaManager;
        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    /** @test */
    public function required_fields_trigger_validation_error()
    {
        // 1. Create Type with Required Field
        $this->schemaManager->createType('Product', [
            ['name' => 'sku', 'type' => 'text', 'settings' => ['required' => true]],
        ]);

        // 2. Attempt to create with empty data via API
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/content/product', []);

        // 3. Assert 422
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    /** @test */
    public function unique_fields_prevent_duplicates()
    {
        // 1. Create Type with Unique Field
        $this->schemaManager->createType('Product', [
            ['name' => 'sku', 'type' => 'text', 'settings' => ['required' => true, 'unique' => true]],
        ]);

        // 2. Create First Product
        $entity = (new DynamicEntity)->bind('product');
        $entity->create(['sku' => 'ABC-123']);

        // 3. Attempt to create Duplicate via API
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/content/product', ['sku' => 'ABC-123']);

        // 4. Assert 422
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    /** @test */
    public function unique_fields_allow_updates_to_self()
    {
        // 1. Create Type with Unique Field
        $this->schemaManager->createType('Product', [
            ['name' => 'sku', 'type' => 'text', 'settings' => ['required' => true, 'unique' => true]],
        ]);

        // 2. Create Product
        $entity = (new DynamicEntity)->bind('product');
        $item = $entity->create(['sku' => 'ABC-123']);

        // 3. Update Self (keep same SKU) via API
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson("/api/content/product/{$item->id}", ['sku' => 'ABC-123']);

        // 4. Assert 200 OK
        $response->assertOk();
    }
}
