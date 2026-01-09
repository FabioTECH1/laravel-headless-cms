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
                    'last_used_at' => $token->last_used_at?->diffForHumans() ?? 'Never',
                    'created_at' => $token->created_at->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = auth()->user()->createToken($request->name);

        return back()->with('token', $token->plainTextToken)
            ->with('success', 'Token created successfully.');
    }

    public function destroyToken($id)
    {
        auth()->user()->tokens()->where('id', $id)->delete();

        return back()->with('success', 'Token revoked successfully.');
    }
}
