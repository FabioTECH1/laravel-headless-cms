<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    Route::post('logout', [LoginController::class, 'destroy'])->name('admin.logout');

    Route::get('/schema', [\App\Http\Controllers\Admin\SchemaController::class, 'index'])->name('admin.schema.index');
    Route::get('/schema/create', [\App\Http\Controllers\Admin\SchemaController::class, 'create'])->name('admin.schema.create');
    Route::post('/schema', [\App\Http\Controllers\Admin\SchemaController::class, 'store'])->name('admin.schema.store');
    Route::get('/schema/{slug}/edit', [\App\Http\Controllers\Admin\SchemaController::class, 'edit'])->name('admin.schema.edit');
    Route::put('/schema/{slug}', [\App\Http\Controllers\Admin\SchemaController::class, 'update'])->name('admin.schema.update');

    Route::get('/content/{slug}', [\App\Http\Controllers\Admin\ContentController::class, 'index'])->name('admin.content.index');
    Route::post('/content/{slug}', [\App\Http\Controllers\Admin\ContentController::class, 'store'])->name('admin.content.store');
    Route::get('/content/{slug}/create', [\App\Http\Controllers\Admin\ContentController::class, 'create'])->name('admin.content.create');
    Route::get('/content/{slug}/{id}/edit', [\App\Http\Controllers\Admin\ContentController::class, 'edit'])->name('admin.content.edit');
    Route::put('/content/{slug}/{id}', [\App\Http\Controllers\Admin\ContentController::class, 'update'])->name('admin.content.update');
    Route::delete('/content/{slug}/{id}', [\App\Http\Controllers\Admin\ContentController::class, 'destroy'])->name('admin.content.destroy');

    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings/profile', [\App\Http\Controllers\Admin\SettingsController::class, 'updateProfile'])->name('admin.settings.profile.update');
    Route::put('/settings/password', [\App\Http\Controllers\Admin\SettingsController::class, 'updatePassword'])->name('admin.settings.password.update');
    Route::post('/settings/tokens', [\App\Http\Controllers\Admin\SettingsController::class, 'storeToken'])->name('admin.settings.tokens.store');
    Route::delete('/settings/tokens/{id}', [\App\Http\Controllers\Admin\SettingsController::class, 'destroyToken'])->name('admin.settings.tokens.destroy');
});
