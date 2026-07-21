<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Http\Controllers\Api\StockBalanceController;
use App\Modules\Stock\Http\Controllers\Api\StockBatchController;
use App\Modules\Stock\Http\Controllers\Api\CompoundProductComponentStockController;
use App\Modules\Stock\Http\Controllers\Api\Reports\StockPositionBatchReportController;

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

// Reports
Route::get('/reports/stockPositionBatch', [StockPositionBatchReportController::class, 'index'])
    ->name('reports.stockPositionBatch');

// Stock Balances
Route::get('/stockBalances/lowStock', [StockBalanceController::class, 'lowStock'])
    ->name('balances.lowStock');

Route::get('/stockBalances/{productId}', [StockBalanceController::class, 'show'])
    // ->where('productId', '[0-9]+')
    ->name('balances.show');

Route::get('/stockBalances', [StockBalanceController::class, 'index'])
    ->name('balances.index');

// Manufacturing
Route::get('/manufacturing/recipeIngredientsStock/{compoundProductId}', [CompoundProductComponentStockController::class, 'index'])
    ->name('manufacturing.recipeIngredientsStock');
