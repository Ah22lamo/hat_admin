<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\QuoteRequestController;
use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get(
        '/categories',
        [CategoryController::class, 'index']
    );

    Route::get(
        '/categories/{category}',
        [CategoryController::class, 'show']
    );

    Route::get(
        '/products',
        [ProductController::class, 'index']
    );

    Route::get(
        '/products/{product}',
        [ProductController::class, 'show']
    );

    Route::post(
        '/quote-requests',
        [QuoteRequestController::class, 'store']
    );

    Route::get(
        '/quote-requests/{quoteRequest}',
        [QuoteRequestController::class, 'show']
    );

    Route::post(
        '/orders',
        [OrderController::class, 'store']
    );

    Route::get(
        '/orders/{order}',
        [OrderController::class, 'show']
    );

});