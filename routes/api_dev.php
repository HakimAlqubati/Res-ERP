<?php

use App\Http\Controllers\Api\Developer\DevInventoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Developer API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('dev')->group(function () {

    // Inventory
    Route::post('/stockSupply', [DevInventoryController::class, 'stockSupply'])->middleware('auth:api');
    Route::post('/stockIssue', [DevInventoryController::class, 'stockIssue'])->middleware('auth:api');
});
