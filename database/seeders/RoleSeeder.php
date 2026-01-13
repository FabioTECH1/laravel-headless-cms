<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $editor = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $author = Role::create(['name' => 'author', 'guard_name' => 'web']);
        $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        // Super Admin - All permissions
        $superAdmin->givePermissionTo(Permission::all());

        // Editor - Content, Media, Schema (no users/roles/settings)
        $editor->givePermissionTo([
            'view-content', 'create-content', 'edit-content', 'delete-content', 'publish-content',
            'view-schema', 'create-schema', 'edit-schema', 'delete-schema',
            'view-media', 'upload-media', 'edit-media', 'delete-media',
        ]);

        // Author - Create/edit own content, upload media
        $author->givePermissionTo([
            'view-content', 'create-content', 'edit-content',
            'view-media', 'upload-media',
        ]);

        // Viewer - Read-only
        $viewer->givePermissionTo([
            'view-content',
            'view-schema',
            'view-media',
        ]);

        // Assign Super Admin role to existing admin users
        User::where('is_admin', true)->each(function ($user) use ($superAdmin) {
            $user->assignRole($superAdmin);
        });
    }
}
