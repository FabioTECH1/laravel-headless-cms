<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->with('permissions:id,name')
            ->get();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Group by prefix (e.g., 'view-content' -> 'content')
            $parts = explode('-', $permission->name);

            return count($parts) > 1 ? $parts[1] : 'other';
        });

        return Inertia::render('Roles/Form', [
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);

            return count($parts) > 1 ? $parts[1] : 'other';
        });

        return Inertia::render('Roles/Form', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(string $id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        // Prevent deletion if users are assigned
        if ($role->users_count > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete role with assigned users. Please reassign users first.',
            ]);
        }

        // Prevent deletion of super-admin role
        if ($role->name === 'super-admin') {
            return back()->withErrors([
                'error' => 'Cannot delete the Super Admin role.',
            ]);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
