<?php

namespace Database\Seeders;

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
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }
}
