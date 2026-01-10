<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // No default user creation. The first user to register via /admin/register becomes the admin.
        $this->call([
            ContentTypeSeeder::class,
        ]);
    }
}
