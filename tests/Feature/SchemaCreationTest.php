<?php

namespace Tests\Feature;

use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\assertDatabaseCount;

uses(RefreshDatabase::class);

test('it creates a physical table when a content type is defined', function () {
    $schemaManager = new SchemaManager;

    $contentType = $schemaManager->createType('Article', [
        ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true]],
        ['name' => 'content', 'type' => 'longtext'],
        ['name' => 'is_published', 'type' => 'boolean'],
    ]);

    expect(Schema::hasTable('articles'))->toBeTrue();
    expect(Schema::hasColumn('articles', 'title'))->toBeTrue();
    expect(Schema::hasColumn('articles', 'content'))->toBeTrue();
    expect(Schema::hasColumn('articles', 'is_published'))->toBeTrue();

    // Check default timestamp/soft delete columns
    expect(Schema::hasColumn('articles', 'created_at'))->toBeTrue();
    expect(Schema::hasColumn('articles', 'updated_at'))->toBeTrue();
    expect(Schema::hasColumn('articles', 'deleted_at'))->toBeTrue();

    assertDatabaseCount('content_types', 1);
    assertDatabaseCount('content_fields', 3);
});

test('it prevents creation if table already exists', function () {
    // Create the table first manually
    Schema::create('blog_posts', function ($table) {
        $table->id();
    });

    $schemaManager = new SchemaManager;

    try {
        $schemaManager->createType('BlogPost', [
            ['name' => 'title', 'type' => 'text'],
        ]);
        $this->fail('Exception not thrown');
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('already exists');
    }

    assertDatabaseCount('content_types', 0);
    assertDatabaseCount('content_fields', 0);

    // Clean up
    Schema::dropIfExists('blog_posts');
});

test('it rolls back if invalid field type is provided', function () {
    $schemaManager = new SchemaManager;

    try {
        $schemaManager->createType('Portfolio', [
            ['name' => 'invalid_col', 'type' => 'unsupported_type'],
        ]);
        $this->fail('Exception not thrown');
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('Unsupported field type');
    }

    assertDatabaseCount('content_types', 0);
    assertDatabaseCount('content_fields', 0);
    expect(Schema::hasTable('portfolios'))->toBeFalse();
});

test('it can add columns to an existing type', function () {
    $schemaManager = new SchemaManager;

    $schemaManager->createType('Product', [
        ['name' => 'name', 'type' => 'text'],
    ]);

    expect(Schema::hasColumn('products', 'price'))->toBeFalse();

    $schemaManager->updateType('product', [
        ['name' => 'price', 'type' => 'integer'],
    ]);

    expect(Schema::hasColumn('products', 'price'))->toBeTrue();
    assertDatabaseCount('content_fields', 2);
});
