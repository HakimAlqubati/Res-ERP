<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Reports\FifoBatchReport\Controllers\FifoBatchReportController;

/*
|--------------------------------------------------------------------------
| Stock API Routes
|--------------------------------------------------------------------------
*/

Route::get('/fifo-batches', [FifoBatchReportController::class, 'index'])
    ->name('fifo-batches.index');

Route::get('/fifo-batches/current-price', [FifoBatchReportController::class, 'currentPrice'])
    ->name('fifo-batches.current-price');
