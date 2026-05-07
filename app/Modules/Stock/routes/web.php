<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Http\Controllers\Reports\GrnConsumptionReportController;

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
