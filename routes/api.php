<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('content')->group(function () {
    Route::middleware([\App\Http\Middleware\CheckContentAccess::class])->group(function () {
        Route::get('/{slug}', [\App\Http\Controllers\Api\ContentController::class, 'index']);
        Route::post('/{slug}', [\App\Http\Controllers\Api\ContentController::class, 'store']);
        Route::get('/{slug}/{id}', [\App\Http\Controllers\Api\ContentController::class, 'show']);
        Route::put('/{slug}/{id}', [\App\Http\Controllers\Api\ContentController::class, 'update']);
        Route::delete('/{slug}/{id}', [\App\Http\Controllers\Api\ContentController::class, 'destroy']);
    });
});
