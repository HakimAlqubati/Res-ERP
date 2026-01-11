<?php
// File: routes/custom_api_route_inventory.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Inventory\InventoryApiController;
use App\Http\Controllers\Api\Inventory\StockInventory\StockInventoryController;
use App\Http\Controllers\Api\Reports\GoodsReceivedNoteReportController;

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('remaining', [InventoryApiController::class, 'remaining'])->name('remaining');
});

// Stock Inventory API Routes
Route::prefix('stockInventories')
    ->middleware(['auth:api'])
    ->name('stockInventories.')
    ->group(function () {
        Route::get('/', [StockInventoryController::class, 'index'])->name('index');
        Route::post('/', [StockInventoryController::class, 'store'])->name('store');
        Route::get('/{id}', [StockInventoryController::class, 'show'])->name('show')->whereNumber('id');
        Route::post('/{id}', [StockInventoryController::class, 'update'])->name('update')->whereNumber('id');
        Route::delete('/{id}', [StockInventoryController::class, 'destroy'])->name('destroy')->whereNumber('id');

        // Additional action
        Route::post('/{id}/finalize', [StockInventoryController::class, 'finalize'])->name('finalize')->whereNumber('id');
    });

// Reports Routes
Route::get('grn', [GoodsReceivedNoteReportController::class, 'index']);
Route::get('grn.pdf', [GoodsReceivedNoteReportController::class, 'exportPdf']);
