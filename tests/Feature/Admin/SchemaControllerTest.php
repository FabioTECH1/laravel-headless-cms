<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('admin can view schema builder', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('admin.schema.create'));

    $response->assertStatus(200);
});

test('admin can submit schema definition', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->post(route('admin.schema.store'), [
        'name' => 'BlogPost',
        'fields' => [
            ['name' => 'title', 'type' => 'text'],
            ['name' => 'content', 'type' => 'longtext'],
        ],
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('content_types', ['name' => 'BlogPost']);
    expect(Schema::hasTable('blog_posts'))->toBeTrue();
});

test('it validates schema input', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->post(route('admin.schema.store'), [
        'name' => '',
        'fields' => [],
    ]);

    $response->assertSessionHasErrors(['name', 'fields']);
});
