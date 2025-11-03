<?php
// File: routes/inventory.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Inventory\InventoryApiController;
use App\Http\Controllers\Api\Reports\GoodsReceivedNoteReportController;
use App\Notifications\WarningNotification;

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('remaining', [InventoryApiController::class, 'remaining'])->name('remaining');
});


// Route::prefix('reports')->group(function () {
// GET /api/reports/grn?per_page=15&product_id[]=1&grn_number[]=GRN-001&supplier_id=5&store_id=2&category_id[]=10&date_from=2025-11-01&date_to=2025-11-03&show_grn_number=true
Route::get('grn', [GoodsReceivedNoteReportController::class, 'index']);

// Optional: PDF export via API (same filters as above)
Route::get('grn.pdf', [GoodsReceivedNoteReportController::class, 'exportPdf']);
// });
