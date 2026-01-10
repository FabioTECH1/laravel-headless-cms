<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function create()
    {
        if (User::exists()) {
            return redirect()->route('admin.login')->with('error', 'Admin account already exists.');
        }

        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        if (User::exists()) {
            return redirect()->route('admin.login')->with('error', 'Admin account already exists.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => true,
        ]);

        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }
}
