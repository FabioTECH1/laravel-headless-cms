<?php

use App\Models\ContentField;
use App\Models\ContentType;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it can create a content type', function () {
    $contentType = ContentType::create([
        'name' => 'Article',
        'slug' => 'article',
        'description' => 'A blog post',
        'is_public' => true,
        'has_ownership' => true,
    ]);

    $this->assertDatabaseHas('content_types', [
        'name' => 'Article',
        'slug' => 'article',
    ]);

    expect($contentType->is_public)->toBeTrue();
    expect($contentType->has_ownership)->toBeTrue();
});

test('it can add fields to a content type', function () {
    $contentType = ContentType::create([
        'name' => 'Product',
        'slug' => 'product',
    ]);

    $field = $contentType->fields()->create([
        'name' => 'Price',
        'type' => 'text',
    ]);

    $this->assertDatabaseHas('content_fields', [
        'name' => 'Price',
        'content_type_id' => $contentType->id,
    ]);

    expect($contentType->refresh()->fields)->toHaveCount(1);
    expect($field->contentType->id)->toBe($contentType->id);
});

test('it casts settings to array', function () {
    $contentType = ContentType::create([
        'name' => 'Page',
        'slug' => 'page',
    ]);

    $field = $contentType->fields()->create([
        'name' => 'Title',
        'type' => 'text',
        'settings' => ['required' => true, 'max_length' => 255],
    ]);

    $retrievedField = ContentField::find($field->id);

    expect($retrievedField->settings)->toBeArray();
    expect($retrievedField->settings['required'])->toBeTrue();
    expect($retrievedField->settings['max_length'])->toBe(255);
});
