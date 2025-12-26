<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landing\WorkbenchController;
use App\Http\Controllers\Docs\FinancialHRReportController;

// Documentation Routes (Protected)
// Route::prefix('docs')->name('docs.')->group(function () {
// });
Route::get('/financial-hr-report', [FinancialHRReportController::class, 'index'])
    ->name('financial-hr-report');

// Route::get('/workbench-erp', [WorkbenchController::class, 'show'])->name('landing.workbench');
// Route::get('/restaurant-erp', [WorkbenchController::class, 'restaurantErp'])->name('landing.restaurant-erp');
// Route::get('/faq', [WorkbenchController::class, 'faq'])->name('landing.faq');