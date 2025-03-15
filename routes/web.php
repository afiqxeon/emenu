<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\ProductController;


Route::get('/{username}', [FrontendController::class, 'index'])->name('index');
Route::get('/{username}/product/{id}', [ProductController::class, 'show'])->name('product.show');
