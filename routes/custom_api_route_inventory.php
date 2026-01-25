<?php
// File: routes/custom_api_route_inventory.php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\Inventory\InventoryApiController;
use App\Http\Controllers\Api\Inventory\StockInventory\StockInventoryController;
use App\Http\Controllers\Api\Inventory\StockAdjustment\StockAdjustmentController;
use App\Http\Controllers\Api\Reports\GoodsReceivedNoteReportController;

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('remaining', [InventoryApiController::class, 'remaining'])->name('remaining');

    // GET /api/inventory/store-categories?store_id=1
    // Returns distinct categories linked to a store through inventory transactions
    Route::get('store-categories', function (\Illuminate\Http\Request $request) {
        if (!$request->filled('store_id')) {
            return response()->json(['error' => 'store_id is required'], 400);
        }

        $storeId = (int) $request->integer('store_id');

        $categories = DB::table('inventory_transactions as it')
            ->join('products as p', 'it.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->where('it.store_id', $storeId)
            ->whereNull('it.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereNull('c.deleted_at')
            ->select('c.id as category_id', 'c.name as category_name')
            ->distinct()
            ->orderBy('c.name')
            ->get();

        return response()->json([
            'success' => true,
            'store_id' => $storeId,
            'data' => $categories,
        ]);
    })->name('store-categories');
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

// Stock Adjustment API Routes
Route::prefix('stockAdjustments')
    ->middleware(['auth:api'])
    ->name('stockAdjustments.')
    ->group(function () {
        Route::get('/', [StockAdjustmentController::class, 'index'])->name('index');
        Route::post('/', [StockAdjustmentController::class, 'store'])->name('store');
        Route::get('/{id}', [StockAdjustmentController::class, 'show'])->name('show')->whereNumber('id');
        Route::delete('/{id}', [StockAdjustmentController::class, 'destroy'])->name('destroy')->whereNumber('id');
    });

// Reports Routes
Route::get('grn', [GoodsReceivedNoteReportController::class, 'index']);
Route::get('grn.pdf', [GoodsReceivedNoteReportController::class, 'exportPdf']);
