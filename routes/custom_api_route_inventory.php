<?php
// File: routes/inventory.php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\Inventory\InventoryApiController;
use App\Http\Controllers\Api\Reports\GoodsReceivedNoteReportController;
use App\Notifications\WarningNotification;

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


// Route::prefix('reports')->group(function () {
// GET /api/reports/grn?per_page=15&product_id[]=1&grn_number[]=GRN-001&supplier_id=5&store_id=2&category_id[]=10&date_from=2025-11-01&date_to=2025-11-03&show_grn_number=true
Route::get('grn', [GoodsReceivedNoteReportController::class, 'index']);

// Optional: PDF export via API (same filters as above)
Route::get('grn.pdf', [GoodsReceivedNoteReportController::class, 'exportPdf']);

Route::get('manufacturing-labels', [\App\Http\Controllers\Api\Reports\ManufacturingProductLabelReportController::class, 'getLabels']);
Route::get('manufacturing-label-details', [\App\Http\Controllers\Api\Reports\ManufacturingProductLabelReportController::class, 'getLabelDetails']);


// });
