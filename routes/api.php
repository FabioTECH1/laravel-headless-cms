<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentController;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public content read routes
Route::get('/content/{slug}', [ContentController::class, 'index']);
Route::get('/content/{slug}/{id}', [ContentController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Content write routes (require authentication)
    Route::post('/content/{slug}', [ContentController::class, 'store']);
    Route::put('/content/{slug}/{id}', [ContentController::class, 'update']);
    Route::delete('/content/{slug}/{id}', [ContentController::class, 'destroy']);
});
