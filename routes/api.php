<?php

use Illuminate\Support\Facades\Route;
use Src\Catalog\Infrastructure\Http\Api\CategoryApiController;
use Src\Catalog\Infrastructure\Http\Api\ProductApiController;
use Src\Identity\Infrastructure\Http\Api\AuthApiController;
use Src\Ordering\Infrastructure\Http\Api\OrderApiController;

// Named "api.*" so the names don't clash with the web routes of the same resources.
Route::name('api.')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthApiController::class, 'me']);
        Route::post('/logout', [AuthApiController::class, 'logout']);

        Route::apiResource('categories', CategoryApiController::class);
        Route::apiResource('products', ProductApiController::class);
        Route::post('orders', [OrderApiController::class, 'store']);
    });
});
