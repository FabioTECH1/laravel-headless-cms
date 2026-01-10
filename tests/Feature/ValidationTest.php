<?php

namespace Tests\Feature;

use App\Models\DynamicEntity;
use App\Models\User;
use App\Services\SchemaManager;

describe('Validation', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->schemaManager = new SchemaManager;
        $this->token = $this->admin->createToken('test')->plainTextToken;
    });

    it('triggers error for required fields', function () {
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
    });

    it('prevents duplicates for unique fields', function () {
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
    });

    it('allows self-update for unique fields', function () {
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
    });
});
