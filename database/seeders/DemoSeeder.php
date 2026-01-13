<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Demo Users (Super Admin, Editor, Author, Viewer)
        $this->call(UserSeeder::class);

        // 2. Demo Media (Images, Folders)
        $this->call(MediaSeeder::class);

        // 3. Demo Content Types & Data (Blog Posts, Pages, Authors, Categories, etc.)
        $this->call(ContentTypeSeeder::class);

        // 4. Demo Webhooks
        $this->call(WebhookSeeder::class);
    }
}
