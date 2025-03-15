<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendController;

Route::get('/{username}', [FrontendController::class, 'index'])->name('index');
