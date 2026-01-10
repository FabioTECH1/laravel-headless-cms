<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RegisterController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\SchemaController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('login', [LoginController::class, 'store']);
});

// First-time setup registration routes, now without 'guest' middleware
Route::get('register', [RegisterController::class, 'create'])->name('admin.register');
Route::post('register', [RegisterController::class, 'store'])->name('admin.register.store');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::post('logout', [LoginController::class, 'destroy'])->name('admin.logout');

    // Schema Management
    Route::get('/schema', [SchemaController::class, 'index'])->name('admin.schema.index');
    Route::get('/schema/create', [SchemaController::class, 'create'])->name('admin.schema.create');
    Route::post('/schema', [SchemaController::class, 'store'])->name('admin.schema.store');
    Route::get('/schema/{slug}/edit', [SchemaController::class, 'edit'])->name('admin.schema.edit');
    Route::put('/schema/{slug}', [SchemaController::class, 'update'])->name('admin.schema.update');
    Route::delete('/schema/{slug}', [SchemaController::class, 'destroy'])->name('admin.schema.destroy');

    // Content Management
    Route::get('/content/{slug}', [ContentController::class, 'index'])->name('admin.content.index');
    Route::post('/content/{slug}', [ContentController::class, 'store'])->name('admin.content.store');
    Route::get('/content/{slug}/create', [ContentController::class, 'create'])->name('admin.content.create');
    Route::get('/content/{slug}/{id}/edit', [ContentController::class, 'edit'])->name('admin.content.edit');
    Route::put('/content/{slug}/{id}', [ContentController::class, 'update'])->name('admin.content.update');
    Route::delete('/content/{slug}/{id}', [ContentController::class, 'destroy'])->name('admin.content.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('admin.settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('admin.settings.password.update');
    Route::post('/settings/tokens', [SettingsController::class, 'storeToken'])->name('admin.settings.tokens.store');
    Route::delete('/settings/tokens/{id}', [SettingsController::class, 'destroyToken'])->name('admin.settings.tokens.destroy');

    // User Management
    Route::resource('users', UserController::class)->names('admin.users')->except(['show', 'create', 'edit']);
    Route::put('users/{user}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
    Route::put('users/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('admin.users.unsuspend');

    // Media
    Route::post('/media', [MediaController::class, 'store'])->name('admin.media.store');
});
