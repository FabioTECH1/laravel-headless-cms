<?php

namespace Tests\Feature;

use App\Models\DynamicEntity;
use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Support\Facades\Schema;

describe('Relationships', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->schemaManager = new SchemaManager;
    });

    it('creates foreign key column in database', function () {
        // 1. Create 'Category' Type
        $categoryType = $this->schemaManager->createType('Category', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        // 2. Create 'Product' Type with Relation to Category
        $this->schemaManager->createType('Product', [
            ['name' => 'name', 'type' => 'text'],
            [
                'name' => 'category',
                'type' => 'relation',
                'settings' => ['related_content_type_id' => $categoryType->id],
            ],
        ]);

        // Assert column exists
        $this->assertTrue(Schema::hasColumn('products', 'category_id'));
    });

    it('loads relation options in create form', function () {
        // 1. Create 'Category' Type
        $categoryType = $this->schemaManager->createType('Category', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        // 2. Create a Category Entry "Tech"
        $categoryEntity = (new DynamicEntity)->bind('category');
        $category = $categoryEntity->create(['title' => 'Tech']);

        // 3. Create 'Product' Type with Relation to Category
        $this->schemaManager->createType('Product', [
            ['name' => 'name', 'type' => 'text'],
            [
                'name' => 'category',
                'type' => 'relation',
                'settings' => ['related_content_type_id' => $categoryType->id],
            ],
        ]);

        // 4. Visit Product Create Page
        $response = $this->actingAs($this->admin)
            ->get(route('admin.content.create', 'product'));

        // 5. Assert Options are passed
        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Content/Form')
                ->has('options.category', 1)
                ->where('options.category.0.id', $category->id)
                ->where('options.category.0.label', 'Tech')
            );
    });
});
