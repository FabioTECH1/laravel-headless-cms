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

    // Schema Management (requires schema permissions)
    Route::middleware('permission:view-schema')->group(function () {
        Route::get('/schema', [SchemaController::class, 'index'])->name('admin.schema.index');
    });
    Route::middleware('permission:create-schema')->group(function () {
        Route::get('/schema/create', [SchemaController::class, 'create'])->name('admin.schema.create');
        Route::post('/schema', [SchemaController::class, 'store'])->name('admin.schema.store');
    });
    Route::middleware('permission:edit-schema')->group(function () {
        Route::get('/schema/{slug}/edit', [SchemaController::class, 'edit'])->name('admin.schema.edit');
        Route::put('/schema/{slug}', [SchemaController::class, 'update'])->name('admin.schema.update');
    });
    Route::middleware('permission:delete-schema')->group(function () {
        Route::delete('/schema/{slug}', [SchemaController::class, 'destroy'])->name('admin.schema.destroy');
    });

    // Content Management (requires content permissions)
    Route::middleware('permission:view-content')->group(function () {
        Route::get('/content/{slug}', [ContentController::class, 'index'])->name('admin.content.index');
    });
    Route::middleware('permission:create-content')->group(function () {
        Route::get('/content/{slug}/create', [ContentController::class, 'create'])->name('admin.content.create');
        Route::post('/content/{slug}', [ContentController::class, 'store'])->name('admin.content.store');
    });
    Route::middleware('permission:edit-content')->group(function () {
        Route::get('/content/{slug}/{id}/edit', [ContentController::class, 'edit'])->name('admin.content.edit');
        Route::put('/content/{slug}/{id}', [ContentController::class, 'update'])->name('admin.content.update');
    });
    Route::middleware('permission:delete-content')->group(function () {
        Route::delete('/content/{slug}/{id}', [ContentController::class, 'destroy'])->name('admin.content.destroy');
    });

    // Settings (requires settings permissions)
    Route::middleware('permission:view-settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    });
    Route::middleware('permission:edit-settings')->group(function () {
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('admin.settings.profile.update');
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('admin.settings.password.update');
    });
    Route::middleware('permission:manage-api-tokens')->group(function () {
        Route::post('/settings/tokens', [SettingsController::class, 'storeToken'])->name('admin.settings.tokens.store');
        Route::delete('/settings/tokens/{id}', [SettingsController::class, 'destroyToken'])->name('admin.settings.tokens.destroy');
    });

    // User Management (requires user permissions)
    Route::middleware('permission:view-users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    });
    Route::middleware('permission:create-users')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    });
    Route::middleware('permission:edit-users')->group(function () {
        Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::put('users/{user}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
        Route::put('users/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('admin.users.unsuspend');
    });
    Route::middleware('permission:delete-users')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // Role Management (Super Admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)->names('admin.roles');

        // Webhooks
        Route::resource('webhooks', \App\Http\Controllers\Admin\WebhookController::class)->names('admin.webhooks')->except(['show', 'create', 'edit']);
        Route::post('webhooks/{webhook}/test', [\App\Http\Controllers\Admin\WebhookController::class, 'test'])->name('admin.webhooks.test');
    });

    // Media Library (requires media permissions)
    Route::middleware('permission:view-media')->group(function () {
        Route::get('/media-library', function () {
            return \Inertia\Inertia::render('Media/Index');
        })->name('admin.media-library');
        Route::get('/media', [MediaController::class, 'index'])->name('admin.media.index');
        Route::get('/media-folders', [\App\Http\Controllers\Admin\MediaFolderController::class, 'index'])->name('admin.media-folders.index');
    });
    Route::middleware('permission:upload-media')->group(function () {
        Route::post('/media', [MediaController::class, 'store'])->name('admin.media.store');
        Route::post('/media-folders', [\App\Http\Controllers\Admin\MediaFolderController::class, 'store'])->name('admin.media-folders.store');
    });
    Route::middleware('permission:edit-media')->group(function () {
        Route::put('/media/{id}', [MediaController::class, 'update'])->name('admin.media.update');
        Route::put('/media-folders/{id}', [\App\Http\Controllers\Admin\MediaFolderController::class, 'update'])->name('admin.media-folders.update');
    });
    Route::middleware('permission:delete-media')->group(function () {
        Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('admin.media.destroy');

        // Media Folders
        Route::get('/media-folders', [\App\Http\Controllers\Admin\MediaFolderController::class, 'index'])->name('admin.media-folders.index');
        Route::post('/media-folders', [\App\Http\Controllers\Admin\MediaFolderController::class, 'store'])->name('admin.media-folders.store');
        Route::put('/media-folders/{id}', [\App\Http\Controllers\Admin\MediaFolderController::class, 'update'])->name('admin.media-folders.update');
        Route::delete('/media-folders/{id}', [\App\Http\Controllers\Admin\MediaFolderController::class, 'destroy'])->name('admin.media-folders.destroy');
    });
});
