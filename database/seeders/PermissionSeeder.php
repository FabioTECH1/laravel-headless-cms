<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Content Permissions
        Permission::create(['name' => 'view-content', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-content', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-content', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete-content', 'guard_name' => 'web']);
        Permission::create(['name' => 'publish-content', 'guard_name' => 'web']);

        // Schema Permissions
        Permission::create(['name' => 'view-schema', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-schema', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-schema', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete-schema', 'guard_name' => 'web']);

        // Media Permissions
        Permission::create(['name' => 'view-media', 'guard_name' => 'web']);
        Permission::create(['name' => 'upload-media', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-media', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete-media', 'guard_name' => 'web']);

        // User Permissions
        Permission::create(['name' => 'view-users', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-users', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-users', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete-users', 'guard_name' => 'web']);

        // Role Permissions
        Permission::create(['name' => 'view-roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete-roles', 'guard_name' => 'web']);

        // Settings Permissions
        Permission::create(['name' => 'view-settings', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-settings', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage-api-tokens', 'guard_name' => 'web']);
    }
}
