<?php

use App\Http\Controllers\Api\Developer\DevInventoryController;
use App\Http\Controllers\Api\Developer\DevBenchmarkController;
use App\Http\Controllers\Api\Developer\DevOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Developer API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('dev')->middleware('auth:api')->group(function () {

    // Inventory Operations
    Route::post('/stockSupply', [DevInventoryController::class, 'stockSupply']);
    Route::post('/stockIssue', [DevInventoryController::class, 'stockIssue']);
    Route::post('/randomPurchase', [DevInventoryController::class, 'randomPurchase']);

    // Orders
    Route::prefix('order')->group(function () {
        Route::get('/{id}/details', [DevOrderController::class, 'details']);
        Route::post('/{id}/moveInventory', [DevOrderController::class, 'moveInventory']);
    });

    // Benchmark
    Route::prefix('benchmark')->group(function () {
        Route::post('/summary', [DevBenchmarkController::class, 'summary']);
        Route::post('/compare', [DevBenchmarkController::class, 'compare']);
    });
});
