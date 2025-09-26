<?php
// File: routes/inventory.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Inventory\InventoryApiController;
use App\Notifications\WarningNotification;

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('remaining', [InventoryApiController::class, 'remaining'])->name('remaining');
});


