<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Http\Controllers\Reports\GrnConsumptionReportController;
use App\Modules\Stock\Http\Controllers\Reports\ProductGrnAggregationReportController;

/*
|--------------------------------------------------------------------------
| Stock Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your Stock module.
| These routes are loaded by the StockServiceProvider.
|
*/

Route::get('/reports/grn-consumption', [GrnConsumptionReportController::class, 'index'])
    ->name('reports.grn-consumption.index');

Route::get('/reports/product-grn-aggregation', [ProductGrnAggregationReportController::class, 'index'])
    ->name('reports.product-grn-aggregation.index');

Route::get('/reports/grn-consumption-items', [GrnConsumptionReportController::class, 'flattenedIndex'])
    ->name('reports.grn-consumption-items.index');

Route::get('/reports/products/search', [GrnConsumptionReportController::class, 'searchProducts'])
    ->name('reports.products.search');
