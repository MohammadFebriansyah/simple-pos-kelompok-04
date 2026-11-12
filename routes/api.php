<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/products', [ProductController::class, 'index'])->middleware('abilities:products:read');
    Route::post('/transactions', [TransactionController::class, 'store'])
        ->middleware(['abilities:transactions:create', 'idempotent']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
        ->middleware('abilities:transactions:read');
});
