<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Http\Controllers\Api\StockBalanceController;
use App\Modules\Stock\Http\Controllers\Api\StockBatchController;
use App\Modules\Stock\Http\Controllers\Api\CompoundProductComponentStockController;

/*
|--------------------------------------------------------------------------
| Stock API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the StockServiceProvider with
| prefix "api/stock" and middleware "api" + "auth:sanctum".
|
*/

// Stock Batches (FIFO)
Route::get('/stockBatches', [StockBatchController::class, 'index'])
    ->name('batches.index');

// Stock Balances
Route::get('/stockBalances/lowStock', [StockBalanceController::class, 'lowStock'])
    ->name('balances.lowStock');

Route::get('/stockBalances/{productId}', [StockBalanceController::class, 'show'])
    // ->where('productId', '[0-9]+')
    ->name('balances.show');

Route::get('/stockBalances', [StockBalanceController::class, 'index'])
    ->name('balances.index');

// Manufacturing
Route::get('/manufacturing/compoundProducts/{compoundProductId}/componentsStock', [CompoundProductComponentStockController::class, 'index'])
    ->name('manufacturing.compoundProducts.componentsStock');
