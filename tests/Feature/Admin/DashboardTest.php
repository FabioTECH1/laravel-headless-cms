<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('stats')
        );
    }

    public function test_dashboard_shows_correct_stats()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(4)->create(['is_admin' => false]); // Total 5 users

        // Create Content Types
        $blogType = ContentType::create([
            'name' => 'Blog Post',
            'slug' => 'blog_posts',
            'is_public' => true,
            'has_ownership' => true,
        ]);

        $productType = ContentType::create([
            'name' => 'Product',
            'slug' => 'products',
            'is_public' => true,
            'has_ownership' => false,
        ]);

        // Create tables for them (mimicking what SchemaManager does, simply)
        Schema::create('blog_posts', function ($table) {
            $table->id();
            $table->timestamps();
        });

        // Insert some dummy data
        DB::table('blog_posts')->insert([['created_at' => now()], ['created_at' => now()], ['created_at' => now()]]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.users', 5)
            ->where('stats.schemas', 2)
            ->has('stats.content_breakdown', 2)
            ->where('stats.content_breakdown.0.slug', 'blog_posts')
            ->where('stats.content_breakdown.0.count', 3)
            ->where('stats.content_breakdown.1.slug', 'products')
            // Count should be 0 for products as table might not exist or empty
             // Note: In controller we check Schema::hasTable. If we didn't create table for products, count is 0.
            ->where('stats.content_breakdown.1.count', 0)
        );
    }
}
