<?php

use Illuminate\Support\Facades\Route;
use Src\Catalog\Infrastructure\Http\Controllers\CategoryController;
use Src\Catalog\Infrastructure\Http\Controllers\ProductController;
use Src\Identity\Infrastructure\Http\Controllers\AuthController;
use Src\Ordering\Infrastructure\Http\Controllers\OrderController;

// Named "api.*" so the names don't clash with the web routes of the same resources.
Route::name('api.')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::post('orders', [OrderController::class, 'store']);
    });
});
