<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->schemaManager = new SchemaManager;
    // SchemaManager generates slug from name. 'BlogPost' -> 'blog_post'.
    $this->schemaManager->createType('BlogPost', [
        ['name' => 'title', 'type' => 'text'],
        ['name' => 'is_awesome', 'type' => 'boolean'],
    ]);
});

test('admin can list content items', function () {
    // Create a dummy item
    (new \App\Models\DynamicEntity)->bind('blog_post')->create(['title' => 'Hello World']);

    $response = $this->actingAs($this->admin)->get(route('admin.content.index', 'blog_post'));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Content/Index')
            ->has('items.data', 1)
            ->where('items.data.0.title', 'Hello World')
            ->where('contentType.slug', 'blog_post')
        );
});

test('admin can create content item', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.content.store', 'blog_post'), [
        'title' => 'New Post',
        'is_awesome' => true,
    ]);

    $response->assertRedirect(route('admin.content.index', 'blog_post'));

    $this->assertDatabaseHas('blog_posts', ['title' => 'New Post']);
});

test('admin can edit content item', function () {
    $item = (new \App\Models\DynamicEntity)->bind('blog_post')->create(['title' => 'Old Title']);

    $response = $this->actingAs($this->admin)->put(route('admin.content.update', ['slug' => 'blog_post', 'id' => $item->id]), [
        'title' => 'Updated Title',
        'is_awesome' => false,
    ]);

    $response->assertRedirect(route('admin.content.index', 'blog_post'));

    $this->assertDatabaseHas('blog_posts', ['id' => $item->id, 'title' => 'Updated Title']);
});

test('admin can delete content item', function () {
    $item = (new \App\Models\DynamicEntity)->bind('blog_post')->create(['title' => 'Start Delete']);

    $response = $this->actingAs($this->admin)->delete(route('admin.content.destroy', ['slug' => 'blog_post', 'id' => $item->id]));

    $this->assertDatabaseMissing('blog_posts', ['id' => $item->id]);
});
