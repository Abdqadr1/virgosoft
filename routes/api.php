<?php

use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'list']);
    Route::post('/orders', [OrderController::class, 'create']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
