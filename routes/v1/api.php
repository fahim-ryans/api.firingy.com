<?php

use App\Http\Controllers\V1\CategoryController;
use App\Http\Controllers\V1\ProductController;
use App\Http\Controllers\V1\SliderController;

use Illuminate\Support\Facades\Route;

Route::get('/slider', [SliderController::class, 'index'])->name('slider');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories');

/**
 * Products routes
 */
Route::prefix('products')->name('products.')->group(function() {
    Route::get('/trending', [ProductController::class, 'trending'])->name('trending');
    Route::get('/featured', [ProductController::class, 'featured'])->name('featured');
});
