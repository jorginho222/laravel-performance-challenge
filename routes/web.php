<?php

use Illuminate\Support\Facades\Route;
use Src\Catalog\Infrastructure\Http\Web\CategoryWebController;
use Src\Catalog\Infrastructure\Http\Web\ProductWebController;
use Src\Identity\Infrastructure\Http\Web\AuthWebController;
use Src\Ordering\Infrastructure\Http\Web\OrderWebController;

Route::redirect('/', '/products');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthWebController::class, 'showRegister']);
    Route::post('/register', [AuthWebController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthWebController::class, 'logout']);

    Route::resource('products', ProductWebController::class)->except('show');
    Route::resource('categories', CategoryWebController::class)->except('show');

    // The cart lives in the browser (Pinia store); the page only needs the ability to order.
    Route::inertia('/cart', 'Ordering/Cart')->middleware('can:place-orders');
    Route::post('/orders', [OrderWebController::class, 'store']);
    Route::get('/orders/{order}', [OrderWebController::class, 'show'])->whereUuid('order');
});
