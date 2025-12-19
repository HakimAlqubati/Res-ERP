<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\TaskProposalsController;

/*
|--------------------------------------------------------------------------
| Tasks Routes
|--------------------------------------------------------------------------
|
| Routes for the Tasks System Improvement Proposals
|
*/

Route::prefix('tasks-system')->name('tasks.')->middleware(['web'])->group(function () {

    // Proposals routes
    Route::prefix('proposals')->name('proposals.')->group(function () {
        Route::get('/', [TaskProposalsController::class, 'index'])->name('index');
        Route::get('/roadmap', [TaskProposalsController::class, 'roadmap'])->name('roadmap');
        Route::get('/{key}', [TaskProposalsController::class, 'show'])->name('show');
    });
});
