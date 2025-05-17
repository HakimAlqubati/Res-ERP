<?php

use App\Http\Controllers\Analytics\BranchConsumptionAnalysisController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\FixFifoController;
use App\Http\Controllers\TestController3;
use App\Http\Controllers\TestController4;
use App\Http\Controllers\TestController5;
use App\Http\Controllers\TestController6;
use App\Models\Audit;
use App\Models\Order;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\Route;

Route::get('/custom-route', function () {
    return response()->json(['message' => 'Hello from custom route']);
});

Route::get('/testGetOrders', [TestController4::class, 'testGetOrders'])->middleware('auth:api');
Route::get('/testGetOrdersDetails/{orderId}', [TestController4::class, 'testGetOrdersDetails'])->middleware('auth:api');

Route::get('/testfifo', [TestController3::class, 'testFifo']);
Route::get('/testQRCode/{id}', [TestController3::class, 'testQRCode'])->name('testQRCode');

Route::get('/currntStock', [TestController3::class, 'currntStock'])->name('currntStock');
Route::get('/lowStock', [TestController3::class, 'lowStock']);

Route::get('/getProductItems/{id}', [TestController3::class, 'getProductItems']);

Route::put('update-device-token', [FcmController::class, 'updateDeviceToken']);
Route::post('send-fcm-notification', [FcmController::class, 'sendFcmNotification']);
Route::get('/testGetBranches', [TestController3::class, 'testGetBranches']);
Route::get('/generatePendingApprovalPreviousOrderDetailsReport', [TestController4::class, 'generatePendingApprovalPreviousOrderDetailsReport']);
Route::get('productsNotInventoried', [TestController4::class, 'missingProducts']);

Route::get('getStockSupplyReport', [TestController4::class, 'getStockSupplyReport']);

Route::get('analyticsBranchConsumption', [BranchConsumptionAnalysisController::class, 'analyze']);

Route::get('analyticsBranchConsumptionComparison', [BranchConsumptionAnalysisController::class, 'compare']);
Route::get('/returnOrders', [TestController4::class, 'returnOrders']);

Route::get('/updateCreatedByInPurchaseInvoice', function () {;
    logger()->info('Started updating created_by in purchase_invoices');

    $updatedIds = [];

    PurchaseInvoice::whereNull('created_by')->chunkById(100, function ($invoices) use (&$updatedIds) {
        foreach ($invoices as $invoice) {
            $audit = Audit::query()
                // where('auditable_type', PurchaseInvoice::class)
                ->where('auditable_id', $invoice->id)
                ->where('auditable_type', PurchaseInvoice::class)

                ->where('event', 'created')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->first();



            if ($audit && $audit->user_id) {
                $invoice->update(['created_by' => $audit->user_id]);
                $updatedIds[] = $invoice->id;
            }
        }
    });

    logger()->info('Finished updating created_by in purchase_invoices');

    return response()->json([
        'message' => 'Updated created_by in purchase_invoices successfully.',
        'updated_ids' => $updatedIds,
        'count' => count($updatedIds),
    ]);
});


Route::get('/stockCostReport', [TestController5::class, 'stockCostReport']);
Route::get('/getProductSummaryPerExcelImport', [TestController5::class, 'getProductSummaryPerExcelImport']);
Route::get('/orderdData', [TestController5::class, 'orderdData']);

Route::get('/orderdDataFromExcelImport', [TestController5::class, 'orderdDataFromExcelImport']);


Route::get('/purchasedVSordered', [TestController5::class, 'purchasedVSordered']);




Route::get('/testAllocateFifo', function () {
    $fifoService = new \App\Services\MultiProductsInventoryService(null, 158, 1, 1);

    $order = Order::find(217);
    $allocations = $fifoService->allocateFIFO(
        158,
        1,
        10,
        $order
    );
    return $allocations;
});


Route::get('/fixFifo', [FixFifoController::class, 'fix']);
Route::get('/fixFifoWithSave', [FixFifoController::class, 'fixFifoWithSave']);

Route::get('/allocateForOrders', [FixFifoController::class, 'allocateForOrders']);


Route::get('/getInData', [TestController6::class, 'getInData']);
Route::get('/getOutData', [TestController6::class, 'getOutData']);
Route::get('/inVSoutReport', [TestController6::class, 'inVSoutReport']);
Route::get('/getFinalComparison', [TestController6::class, 'getFinalComparison']);
