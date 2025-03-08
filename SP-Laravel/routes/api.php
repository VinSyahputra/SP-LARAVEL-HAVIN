<?php

use App\Http\Controllers\Api\CustomersController;
use App\Http\Controllers\Api\OrdersController;
use Illuminate\Support\Facades\Route;


Route::prefix('customers')
    ->controller(CustomersController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('{customerId}', 'show');
        Route::post('/', 'store');
        Route::put('{customerId}', 'update');
        Route::delete('{customerId}', 'destroy');
    });

Route::prefix('orders')
    ->controller(OrdersController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('{orderId}', 'show');
        Route::post('/', 'store');
        Route::put('{orderId}', 'update');
        Route::delete('{orderId}', 'destroy');
    });
