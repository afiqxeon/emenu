<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;


Route::get('/{username}', [FrontendController::class, 'index'])->name('index');

Route::get('/{username}/find-product', [ProductController::class, 'find'])->name('product.find');
Route::get('/{username}/find-product/results', [ProductController::class, 'findResults'])->name('product.findResults');
Route::get('/{username}/product/{id}', [ProductController::class, 'show'])->name('product.show');

Route::get('/{username}/cart', [TransactionController::class, 'cart'])->name('cart');