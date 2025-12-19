<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\MaintenanceProposalsController;

/*
|--------------------------------------------------------------------------
| Maintenance Routes
|--------------------------------------------------------------------------
|
| Routes for the Maintenance System Improvement Proposals
|
*/

Route::prefix('maintenance')->name('maintenance.')->middleware(['web'])->group(function () {

    // Proposals routes
    Route::prefix('proposals')->name('proposals.')->group(function () {
        Route::get('/', [MaintenanceProposalsController::class, 'index'])->name('index');
        Route::get('/roadmap', [MaintenanceProposalsController::class, 'roadmap'])->name('roadmap');
        Route::get('/export', [MaintenanceProposalsController::class, 'export'])->name('export');
        Route::get('/{key}', [MaintenanceProposalsController::class, 'show'])->name('show');
    });
});
