<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );
        $admin->assignRole('super-admin');

        // 2. Editor
        $editor = User::firstOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'John Editor',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );
        $editor->assignRole('editor');

        // 3. Author
        $author = User::firstOrCreate(
            ['email' => 'author@example.com'],
            [
                'name' => 'Jane Author',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );
        $author->assignRole('author');

        // 4. Viewer
        $viewer = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Bob Viewer',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );
        $viewer->assignRole('viewer');

    }
}
