<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can list schemas', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $manager = new SchemaManager;
    $manager->createType('BlogPost', [['name' => 'title', 'type' => 'text']]);

    $response = $this->actingAs($admin)->get(route('admin.schema.index'));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Schema/Index')
            ->has('types', 1)
            ->where('types.0.name', 'BlogPost')
        );
});
