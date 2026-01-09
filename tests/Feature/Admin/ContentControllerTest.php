<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->schemaManager = new SchemaManager;
    // SchemaManager generates slug from name. 'Blog Post' -> 'blog-post'.
    $this->schemaManager->createType('Blog Post', [
        ['name' => 'title', 'type' => 'text'],
        ['name' => 'is_awesome', 'type' => 'boolean'],
    ]);
});

test('admin can list content items', function () {
    // Create a dummy item
    (new \App\Models\DynamicEntity)->bind('blog-post')->create(['title' => 'Hello World', 'is_awesome' => false]);

    $response = $this->actingAs($this->admin)->get(route('admin.content.index', 'blog-post'));

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Content/Index')
            ->has('items.data', 1)
            ->where('items.data.0.title', 'Hello World')
            ->where('contentType.slug', 'blog-post')
        );
});

test('admin can create content item with html', function () {
    // 1. Create Type
    $this->schemaManager->createType('Html Post', [
        ['name' => 'title', 'type' => 'text'],
        ['name' => 'body', 'type' => 'longtext'],
    ]);

    // 2. Create Content via Admin
    $response = $this->actingAs($this->admin)->post(route('admin.content.store', 'html-post'), [
        'title' => 'My HTML Post',
        'body' => '<p>This is <strong>bold</strong> text.</p>',
    ]);

    // 3. Assert Redirect & Database
    $response->assertRedirect(route('admin.content.index', 'html-post'));
    $this->assertDatabaseHas('html_posts', [
        'title' => 'My HTML Post',
        'body' => '<p>This is <strong>bold</strong> text.</p>',
    ]);
});

test('admin can create content item', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.content.store', 'blog-post'), [
        'title' => 'New Post',
        'is_awesome' => true,
    ]);

    $response->assertRedirect(route('admin.content.index', 'blog-post'));

    $this->assertDatabaseHas('blog_posts', ['title' => 'New Post']);
});

test('admin can edit content item', function () {
    $item = (new \App\Models\DynamicEntity)->bind('blog-post')->create(['title' => 'Old Title', 'is_awesome' => false]);

    $response = $this->actingAs($this->admin)->put(route('admin.content.update', ['slug' => 'blog-post', 'id' => $item->id]), [
        'title' => 'Updated Title',
        'is_awesome' => false,
    ]);

    $response->assertRedirect(route('admin.content.index', 'blog-post'));

    $this->assertDatabaseHas('blog_posts', ['id' => $item->id, 'title' => 'Updated Title']);
});

test('admin can delete content item', function () {
    $item = (new \App\Models\DynamicEntity)->bind('blog-post')->create(['title' => 'Start Delete', 'is_awesome' => false]);

    $response = $this->actingAs($this->admin)->delete(route('admin.content.destroy', ['slug' => 'blog-post', 'id' => $item->id]));

    $this->assertDatabaseMissing('blog_posts', ['id' => $item->id]);
});
