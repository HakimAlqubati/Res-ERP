<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Http\Controllers\Api\StockBalanceController;

/*
|--------------------------------------------------------------------------
| Stock API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the StockServiceProvider with
| prefix "api/stock" and middleware "api" + "auth:sanctum".
|
*/

Route::get('/stockBalances/lowStock', [StockBalanceController::class, 'lowStock'])
    ->name('balances.lowStock');

Route::get('/stockBalances/{productId}', [StockBalanceController::class, 'show'])
    ->where('productId', '[0-9]+')
    ->name('balances.show');

Route::get('/stockBalances', [StockBalanceController::class, 'index'])
    ->name('balances.index');
