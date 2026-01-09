<?php

namespace Tests\Feature;

use App\Models\DynamicEntity;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it can bind to a dynamic table', function () {
    $schemaManager = new SchemaManager;
    $schemaManager->createType('Product', [
        ['name' => 'name', 'type' => 'text'],
    ]);

    $model = (new DynamicEntity)->bind('product');

    expect($model->getTable())->toBe('products');
});

test('it can create and retrieve data', function () {
    $schemaManager = new SchemaManager;
    $schemaManager->createType('Product', [
        ['name' => 'name', 'type' => 'text'],
        ['name' => 'price', 'type' => 'integer'],
    ]);

    $model = (new DynamicEntity)->bind('product')->create([
        'name' => 'Laptop',
        'price' => 1200,
    ]);

    expect($model->exists)->toBeTrue();

    $retrieved = (new DynamicEntity)->bind('product')->find($model->id);
    expect($retrieved->name)->toBe('Laptop')
        ->and($retrieved->price)->toBe(1200);
});

test('it applies dynamic casting', function () {
    $schemaManager = new SchemaManager;
    $schemaManager->createType('Task', [
        ['name' => 'is_completed', 'type' => 'boolean'],
        ['name' => 'due_date', 'type' => 'datetime'],
    ]);

    $model = (new DynamicEntity)->bind('task')->create([
        'is_completed' => true,
        'due_date' => now(),
    ]);

    $retrieved = (new DynamicEntity)->bind('task')->find($model->id);

    expect($retrieved->is_completed)->toBeTrue()
        ->and($retrieved->is_completed)->toBeBool()
        ->and($retrieved->due_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
