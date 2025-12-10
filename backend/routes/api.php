<?php

use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\TokenController;
use App\Http\Controllers\API\TradeController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;


Route::post('/authenticate', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/commission/all-time', [TradeController::class, 'allTimeCommission']);

    Route::get('/profile', [UserController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'list']);
    Route::post('/orders', [OrderController::class, 'create']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::get('/tokens', [TokenController::class, 'list']);
    Route::get('/tokens/add', [TokenController::class, 'add']);
});
