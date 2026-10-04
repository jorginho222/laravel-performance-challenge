<?php

use Illuminate\Support\Facades\Route;
use Src\Catalog\Infrastructure\Http\Web\CategoryController;
use Src\Catalog\Infrastructure\Http\Web\ProductController;
use Src\Identity\Infrastructure\Http\Web\AuthController;
use Src\Ordering\Infrastructure\Http\Web\OrderController;

Route::redirect('/', '/products');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::resource('products', ProductController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');

    // The cart lives in the browser (Pinia store); the page only needs the ability to order.
    Route::inertia('/cart', 'Ordering/Cart')->middleware('can:place-orders');
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show'])->whereUuid('order');
});
