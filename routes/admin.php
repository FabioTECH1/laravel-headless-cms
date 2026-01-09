<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    Route::post('logout', [LoginController::class, 'destroy'])->name('admin.logout');

    Route::get('/schema', [\App\Http\Controllers\Admin\SchemaController::class, 'index'])->name('admin.schema.index');
    Route::get('/schema/create', [\App\Http\Controllers\Admin\SchemaController::class, 'create'])->name('admin.schema.create');
    Route::post('/schema', [\App\Http\Controllers\Admin\SchemaController::class, 'store'])->name('admin.schema.store');

    Route::get('/content/{slug}', [\App\Http\Controllers\Admin\ContentController::class, 'index'])->name('admin.content.index');
    Route::post('/content/{slug}', [\App\Http\Controllers\Admin\ContentController::class, 'store'])->name('admin.content.store');
    Route::get('/content/{slug}/create', [\App\Http\Controllers\Admin\ContentController::class, 'create'])->name('admin.content.create');
    Route::get('/content/{slug}/{id}/edit', [\App\Http\Controllers\Admin\ContentController::class, 'edit'])->name('admin.content.edit');
    Route::put('/content/{slug}/{id}', [\App\Http\Controllers\Admin\ContentController::class, 'update'])->name('admin.content.update');
    Route::delete('/content/{slug}/{id}', [\App\Http\Controllers\Admin\ContentController::class, 'destroy'])->name('admin.content.destroy');
});
