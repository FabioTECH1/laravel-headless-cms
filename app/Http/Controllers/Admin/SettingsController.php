<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'tokens' => auth()->user()->tokens()->latest()->get()->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->diffForHumans() ?? 'Never',
                    'created_at' => $token->created_at->format('Y-m-d H:i'),
                ];
            }),
            'availableAbilities' => [
                ['value' => '*', 'label' => 'Full Access', 'description' => 'All permissions'],
                ['value' => 'content:read', 'label' => 'Content: Read', 'description' => 'View content via API'],
                ['value' => 'content:write', 'label' => 'Content: Write', 'description' => 'Create/update content'],
                ['value' => 'content:delete', 'label' => 'Content: Delete', 'description' => 'Delete content'],
                ['value' => 'media:read', 'label' => 'Media: Read', 'description' => 'View media via API'],
                ['value' => 'media:write', 'label' => 'Media: Write', 'description' => 'Upload media'],
                ['value' => 'media:delete', 'label' => 'Media: Delete', 'description' => 'Delete media'],
            ],
            'systemInfo' => [
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
            ],
        ]);
    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string|in:content:read,content:write,content:delete,media:read,media:write,media:delete,*',
        ]);

        $abilities = $request->abilities ?? ['*'];

        $token = auth()->user()->createToken($request->name, $abilities);

        return back()->with('token', $token->plainTextToken)
            ->with('success', 'Token created successfully.');
    }

    public function destroyToken($id)
    {
        auth()->user()->tokens()->where('id', $id)->delete();

        return back()->with('success', 'Token revoked successfully.');
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.auth()->id()],
        ]);

        auth()->user()->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function expiredPassword()
    {
        return Inertia::render('Auth/PasswordExpired');
    }

    public function updateExpiredPassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => bcrypt($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Password updated successfully.');
    }
}
