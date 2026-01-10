<?php

namespace Database\Seeders;

use App\Models\ContentType;
use App\Models\DynamicEntity;
use App\Services\SchemaManager;
use Illuminate\Database\Seeder;

class ContentTypeSeeder extends Seeder
{
    public function __construct(
        protected SchemaManager $schemaManager
    ) {}

    public function run(): void
    {
        // 1. Create 'Category' Type
        // text, boolean
        if (! ContentType::where('name', 'Category')->exists()) {
            $category = $this->schemaManager->createType('Category', [
                [
                    'name' => 'title',
                    'type' => 'text',
                    'settings' => ['required' => true, 'unique' => true],
                ],
                [
                    'name' => 'is_active',
                    'type' => 'boolean',
                    'settings' => ['required' => false],
                ],
            ], isPublic: true, hasOwnership: false);

            $this->command->info('Created "Category" Content Type.');
        } else {
            $category = ContentType::where('name', 'Category')->first();
            $this->command->warn('"Category" Content Type already exists.');
        }

        // 2. Create 'Author' Type
        // text, longtext, datetime, media
        if (! ContentType::where('name', 'Author')->exists()) {
            $author = $this->schemaManager->createType('Author', [
                [
                    'name' => 'full_name',
                    'type' => 'text',
                    'settings' => ['required' => true],
                ],
                [
                    'name' => 'bio',
                    'type' => 'longtext',
                    'settings' => ['required' => false],
                ],
                [
                    'name' => 'birth_date',
                    'type' => 'datetime',
                    'settings' => ['required' => false],
                ],
                [
                    'name' => 'avatar',
                    'type' => 'media',
                    'settings' => ['required' => false],
                ],
            ], isPublic: true, hasOwnership: false);

            $this->command->info('Created "Author" Content Type.');
        } else {
            $author = ContentType::where('name', 'Author')->first();
            $this->command->warn('"Author" Content Type already exists.');
        }

        // 3. Create 'BlogPost' Type
        // Relations to Category and Author, plus integer (views)
        if (! ContentType::where('name', 'BlogPost')->exists()) {
            $this->schemaManager->createType('BlogPost', [
                [
                    'name' => 'title',
                    'type' => 'text',
                    'settings' => ['required' => true, 'unique' => true],
                ],
                [
                    'name' => 'excerpt',
                    'type' => 'longtext',
                    'settings' => ['required' => true],
                ],
                [
                    'name' => 'content',
                    'type' => 'longtext',
                    'settings' => ['required' => true],
                ],
                [
                    'name' => 'view_count',
                    'type' => 'integer',
                    'settings' => ['required' => false],
                ],
                [
                    'name' => 'cover_image',
                    'type' => 'media',
                    'settings' => ['required' => false],
                ],
                [
                    'name' => 'category',
                    'type' => 'relation',
                    'settings' => [
                        'required' => true,
                        'related_content_type_id' => $category->id,
                    ],
                ],
                [
                    'name' => 'author',
                    'type' => 'relation',
                    'settings' => [
                        'required' => true,
                        'related_content_type_id' => $author->id,
                    ],
                ],
            ], isPublic: true, hasOwnership: true);

            $this->command->info('Created "BlogPost" Content Type (with Relations).');
        } else {
            $this->command->warn('"BlogPost" Content Type already exists.');
        }

        // --- Data Seeding ---

        // Seed Categories
        $categoryModel = (new DynamicEntity)->bind('category');
        if ($categoryModel->count() === 0) {
            $categories = ['Technology', 'Lifestyle', 'Tutorials'];
            foreach ($categories as $cat) {
                $categoryModel->create([
                    'title' => $cat,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'published_at' => now(),
                ]);
            }
            $this->command->info('Seeded Categories.');
        }

        // Seed Authors
        $authorModel = (new DynamicEntity)->bind('author');
        if ($authorModel->count() === 0) {
            $authors = [
                ['full_name' => 'John Doe', 'bio' => 'Tech enthusiast and writer.'],
                ['full_name' => 'Jane Smith', 'bio' => 'Traveler and lifestyle blogger.'],
            ];
            foreach ($authors as $auth) {
                $authorModel->create([
                    'full_name' => $auth['full_name'],
                    'bio' => $auth['bio'],
                    'birth_date' => now()->subYears(rand(25, 40)),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'published_at' => now(),
                ]);
            }
            $this->command->info('Seeded Authors.');
        }

        // Seed Blog Posts
        $blogModel = (new DynamicEntity)->bind('blogpost');
        if ($blogModel->count() === 0) {
            $cats = $categoryModel->pluck('id')->toArray();
            $auths = $authorModel->pluck('id')->toArray();

            for ($i = 1; $i <= 5; $i++) {
                $blogModel->create([
                    'title' => "Blog Post #{$i}",
                    'excerpt' => "This is a short excerpt for blog post number {$i}.",
                    'content' => '<p><strong>Lorem ipsum</strong> dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
                    'view_count' => rand(10, 1000),
                    'category_id' => $cats[array_rand($cats)],
                    'author_id' => $auths[array_rand($auths)],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'published_at' => now(),
                    'user_id' => \App\Models\User::first()->id ?? \App\Models\User::factory()->create()->id,
                ]);
            }
            $this->command->info('Seeded Blog Posts.');
        }
    }
}
