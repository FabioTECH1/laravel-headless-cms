<?php

namespace Database\Seeders;

use App\Models\ContentType;
use App\Models\DynamicEntity;
use App\Services\SchemaManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ContentTypeSeeder extends Seeder
{
    public function __construct(
        protected SchemaManager $schemaManager
    ) {}

    public function run(): void
    {
        // 0. Cleanup potentially orphaned tables from previous runs
        Schema::dropIfExists('categories');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('blog_post_tag');      // Pivot M2M
        Schema::dropIfExists('blog_post_category'); // Pivot if M2M
        Schema::dropIfExists('author_blog_post');   // Pivot if M2M

        // --- 1. Define Components (No Tables, just Schema) ---

        // SEO Metadata Component
        if (! ContentType::where('name', 'SeoMetadata')->exists()) {
            $this->schemaManager->createType('SeoMetadata', [
                ['name' => 'meta_title', 'type' => 'text', 'settings' => ['required' => true]],
                ['name' => 'meta_description', 'type' => 'longtext', 'settings' => ['required' => false]],
                ['name' => 'social_image', 'type' => 'media', 'settings' => ['required' => false]],
            ], isComponent: true);
            $this->command->info('Created "SeoMetadata" Component.');
        }

        // CTA Block Component
        if (! ContentType::where('name', 'CtaBlock')->exists()) {
            $this->schemaManager->createType('CtaBlock', [
                ['name' => 'headline', 'type' => 'text', 'settings' => ['required' => true]],
                ['name' => 'subheadline', 'type' => 'text', 'settings' => ['required' => false]],
                ['name' => 'button_text', 'type' => 'text', 'settings' => ['required' => true]],
                ['name' => 'button_url', 'type' => 'text', 'settings' => ['required' => true]],
                ['name' => 'style', 'type' => 'text', 'settings' => ['default' => 'primary']], // Simulating Enum
            ], isComponent: true);
            $this->command->info('Created "CtaBlock" Component.');
        }

        // Rich Text Block Component
        if (! ContentType::where('name', 'RichTextBlock')->exists()) {
            $this->schemaManager->createType('RichTextBlock', [
                ['name' => 'content', 'type' => 'longtext', 'settings' => ['required' => true]],
                ['name' => 'alignment', 'type' => 'text', 'settings' => ['default' => 'left']],
            ], isComponent: true);
            $this->command->info('Created "RichTextBlock" Component.');
        }

        // --- 2. Define Content Types ---

        // Tag Type
        if (! ContentType::where('name', 'Tag')->exists()) {
            $this->schemaManager->createType('Tag', [
                ['name' => 'name', 'type' => 'text', 'settings' => ['required' => true, 'unique' => true]],
            ], isPublic: true, hasOwnership: false);
            $this->command->info('Created "Tag" Content Type.');
        }

        // Category Type
        if (! ContentType::where('name', 'Category')->exists()) {
            $category = $this->schemaManager->createType('Category', [
                ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true, 'unique' => true]],
                ['name' => 'is_active', 'type' => 'boolean', 'settings' => ['required' => false]],
                ['name' => 'icon', 'type' => 'media', 'settings' => ['required' => false]], // Added Icon
            ], isPublic: true, hasOwnership: false);
            $this->command->info('Created "Category" Content Type.');
        } else {
            $category = ContentType::where('name', 'Category')->first();
        }

        // Author Type
        if (! ContentType::where('name', 'Author')->exists()) {
            $author = $this->schemaManager->createType('Author', [
                ['name' => 'full_name', 'type' => 'text', 'settings' => ['required' => true]],
                ['name' => 'bio', 'type' => 'longtext', 'settings' => ['required' => false]],
                ['name' => 'birth_date', 'type' => 'datetime', 'settings' => ['required' => false]],
                ['name' => 'avatar', 'type' => 'media', 'settings' => ['required' => false]],
                ['name' => 'social_handle', 'type' => 'text', 'settings' => ['required' => false]], // Added Handle
            ], isPublic: true, hasOwnership: false);
            $this->command->info('Created "Author" Content Type.');
        } else {
            $author = ContentType::where('name', 'Author')->first();
        }

        // BlogPost Type
        if (! ContentType::where('name', 'BlogPost')->exists()) {
            $this->schemaManager->createType('BlogPost', [
                ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true, 'unique' => true]],
                ['name' => 'slug', 'type' => 'text', 'settings' => ['required' => true, 'unique' => true]], // Added explicit slug
                ['name' => 'excerpt', 'type' => 'longtext', 'settings' => ['required' => true]],
                ['name' => 'content', 'type' => 'longtext', 'settings' => ['required' => true]],
                ['name' => 'view_count', 'type' => 'integer', 'settings' => ['required' => false]],
                ['name' => 'cover_image', 'type' => 'media', 'settings' => ['required' => false]],
                ['name' => 'category', 'type' => 'relation', 'settings' => ['required' => true, 'related_content_type_id' => $category->id]],
                ['name' => 'author', 'type' => 'relation', 'settings' => ['required' => true, 'related_content_type_id' => $author->id]],
                ['name' => 'seo', 'type' => 'component', 'settings' => ['component' => 'SeoMetadata']], // Using Component
                ['name' => 'tags', 'type' => 'relation', 'settings' => ['multiple' => true, 'related_content_type_id' => ContentType::where('name', 'Tag')->first()->id]], // M2M
            ], isPublic: true, hasOwnership: true);
            $this->command->info('Created "BlogPost" Content Type (with Relations and Components).');
        }

        // Page Type (Static Pages with Dynamic Zone)
        if (! ContentType::where('name', 'Page')->exists()) {
            $this->schemaManager->createType('Page', [
                ['name' => 'title', 'type' => 'text', 'settings' => ['required' => true]],
                ['name' => 'slug', 'type' => 'text', 'settings' => ['required' => true, 'unique' => true]],
                ['name' => 'sections', 'type' => 'dynamic_zone', 'settings' => ['allowed_components' => ['CtaBlock', 'RichTextBlock']]],
                ['name' => 'seo', 'type' => 'component', 'settings' => ['component' => 'SeoMetadata']],
            ], isPublic: true, hasOwnership: false);
            $this->command->info('Created "Page" Content Type.');
        }

        // --- 3. Data Seeding ---

        $users = \App\Models\User::all();
        $mediaFiles = \App\Models\MediaFile::all();

        // Seed Tags
        $tagModel = (new DynamicEntity)->bind('tag');
        if ($tagModel->count() === 0) {
            $tags = ['News', 'Update', 'Featured', 'Hot Topic', 'Guide'];
            foreach ($tags as $tag) {
                try {
                    $tagModel->firstOrCreate(['name' => $tag], [
                        'created_at' => now(), 'updated_at' => now(), 'published_at' => now(),
                    ]);
                } catch (\Exception $e) {
                }
            }
            $this->command->info('Seeded Tags.');
        }

        // Seed Categories
        $categoryModel = (new DynamicEntity)->bind('category');
        if ($categoryModel->count() === 0) {
            $categories = ['Technology', 'Lifestyle', 'Tutorials', 'Travel', 'Food'];
            foreach ($categories as $cat) {
                try {
                    $categoryModel->firstOrCreate(['title' => $cat], [
                        'is_active' => true,
                        'icon_id' => $mediaFiles->isNotEmpty() ? $mediaFiles->random()->id : null,
                        'created_at' => now(), 'updated_at' => now(), 'published_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("Failed to seed category '{$cat}': ".$e->getMessage());
                }
            }
            $this->command->info('Seeded Categories.');
        }

        // Seed Authors
        $authorModel = (new DynamicEntity)->bind('author');
        if ($authorModel->count() === 0) {
            $authors = [
                ['full_name' => 'John Doe', 'bio' => 'Tech enthusiast.', 'handle' => '@johndoe'],
                ['full_name' => 'Jane Smith', 'bio' => 'Lifestyle guru.', 'handle' => '@janesmith'],
                ['full_name' => 'Alice Johnson', 'bio' => 'Professional Chef.', 'handle' => '@alicecooks'],
            ];
            foreach ($authors as $auth) {
                try {
                    $authorModel->firstOrCreate(['full_name' => $auth['full_name']], [
                        'bio' => $auth['bio'],
                        'birth_date' => now()->subYears(rand(25, 40)),
                        'avatar_id' => $mediaFiles->isNotEmpty() ? $mediaFiles->random()->id : null,
                        'social_handle' => $auth['handle'],
                        'created_at' => now(), 'updated_at' => now(), 'published_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("Failed to seed author '{$auth['full_name']}': ".$e->getMessage());
                }
            }
            $this->command->info('Seeded Authors.');
        }

        // Seed Pages
        $pageModel = (new DynamicEntity)->bind('page');
        if ($pageModel->count() === 0) {
            $pages = [
                [
                    'title' => 'Home', 'slug' => 'home',
                    'sections' => [
                        ['__component' => 'richtextblock', 'content' => '<h1>Welcome to our Blog!</h1><p>The best content provided daily.</p>', 'alignment' => 'center'],
                        ['__component' => 'ctablock', 'headline' => 'Subscribe Now', 'subheadline' => 'Get the latest updates.', 'button_text' => 'Join Us', 'button_url' => '/subscribe', 'style' => 'primary'],
                    ],
                ],
                [
                    'title' => 'About Us', 'slug' => 'about',
                    'sections' => [
                        ['__component' => 'richtextblock', 'content' => '<p>We are a team of passionate writers.</p>', 'alignment' => 'left'],
                    ],
                ],
            ];
            foreach ($pages as $p) {
                try {
                    $pageModel->firstOrCreate(['slug' => $p['slug']], [
                        'title' => $p['title'],
                        'sections' => $p['sections'],
                        'seo' => [
                            'meta_title' => $p['title'].' - My Blog',
                            'meta_description' => "Read more about {$p['title']}",
                            'social_image' => $mediaFiles->isNotEmpty() ? $mediaFiles->random()->id : null, // Assuming media ID stored in component JSON is handled
                        ],
                        'created_at' => now(), 'updated_at' => now(), 'published_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("Failed to seed page '{$p['title']}': ".$e->getMessage());
                }
            }
            $this->command->info('Seeded Pages.');
        }

        // Seed Blog Posts
        $blogModel = (new DynamicEntity)->bind('blogpost');
        if ($blogModel->count() === 0) {
            $cats = $categoryModel->pluck('id')->toArray();
            $auths = $authorModel->pluck('id')->toArray();
            $tagIds = $tagModel->pluck('id')->toArray();

            for ($i = 1; $i <= 10; $i++) {
                try {
                    $title = "The Future of Tech: Trends to Watch #{$i}";
                    $post = $blogModel->firstOrCreate(
                        ['title' => $title],
                        [
                            'slug' => \Illuminate\Support\Str::slug($title),
                            'excerpt' => "Discover the emerging technologies that will shape our world. Post {$i}.",
                            'content' => '<p><strong>Lorem ipsum</strong> dolor sit amet.</p>',
                            'view_count' => rand(100, 5000),
                            'category_id' => $cats[array_rand($cats)],
                            'author_id' => $auths[array_rand($auths)],
                            'cover_image_id' => $mediaFiles->isNotEmpty() ? $mediaFiles->random()->id : null,
                            'seo' => [
                                'meta_title' => $title,
                                'meta_description' => "Read about {$title}",
                            ],
                            'created_at' => now()->subDays(rand(1, 30)),
                            'updated_at' => now(),
                            'published_at' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
                            'user_id' => $users->random()->id,
                        ]
                    );

                    // Attach Tags (M2M)
                    if ($post->wasRecentlyCreated && ! empty($tagIds)) {
                        $post->tags()->sync(array_rand(array_flip($tagIds), rand(1, 3)));
                    }

                } catch (\Exception $e) {
                    $this->command->error("Failed to seed blog post #{$i}: ".$e->getMessage());
                }
            }
            $this->command->info('Seeded Blog Posts.');
        }
    }
}
