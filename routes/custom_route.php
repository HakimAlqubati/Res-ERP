<?php

use App\Http\Controllers\Analytics\BranchConsumptionAnalysisController;
use App\Http\Controllers\Api\ProductPriceHistoryController;
use App\Http\Controllers\CopyOrderOutToBranchStoreController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\FixFifoController;
use App\Http\Controllers\FixOrderWithFifoController;
use App\Http\Controllers\TestController3;
use App\Http\Controllers\TestController4;
use App\Http\Controllers\TestController5;
use App\Http\Controllers\TestController6;
use App\Http\Controllers\TestController7;
use App\Http\Controllers\TestController8;
use App\Models\Audit;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\StockIssueOrder;
use App\Services\FifoMethodService;
use App\Services\FixFifo\FifoAllocatorService;
use App\Services\UnitPriceFifoUpdater;
use Illuminate\Support\Facades\DB;
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

    $order = Order::find(305);
    $stockIssue = StockIssueOrder::find(1);
    $fifoService = new \App\Services\FifoMethodService($stockIssue);
    // $updated = [];
    // $products = Product::whereIn('id', [1, 2, 3, 4, 5])->select('id', 'name')->with('allUnitPrices')->get();
    // foreach ($products as  $product) {
    //     $unitPrices = $product->allUnitPrices;

    //     foreach ($unitPrices as $unitPrice) {
    //         $fifoService = new FifoMethodService();

    //         $allocations[$unitPrice->unit_id] = $fifoService->getAllocateFifo(
    //             $unitPrice->product_id,
    //             $unitPrice->unit_id,
    //             0.0000001
    //         );
    //     }
    //     $updated[$product->id] = $allocations;
    // }
    // return $updated;
    return (new FifoAllocatorService())->allocate($_GET['product_id']);
    $allocations = $fifoService->getAllocateFifo(
        $_GET['product_id'],
        $_GET['unit_id'],
        $_GET['qty']
    );
    return $allocations;
});
Route::get('/testUpdateUnitPrice', function () {
    $productId = $_GET['product_id'];
    $serevice = UnitPriceFifoUpdater::updatePriceUsingFifo($productId);
    return $serevice;
});


Route::get('/fixFifo', [FixFifoController::class, 'fix']);
Route::get('/fixFifoWithSave', [FixFifoController::class, 'fixFifoWithSave']);

Route::get('/allocateForOrders', [FixFifoController::class, 'allocateForOrders']);


Route::get('/getInData', [TestController6::class, 'getInData']);
Route::get('/getOutData', [TestController6::class, 'getOutData']);
Route::get('/inVSoutReport', [TestController6::class, 'inVSoutReport']);
Route::get('/getFinalComparison', [TestController6::class, 'getFinalComparison']);
Route::get('/storeInventoryTransctionInForBranchStoresFromOrders', [TestController6::class, 'storeInventoryTransctionInForBranchStoresFromOrders']);
Route::get('/updatePriceUsingFifo', [TestController7::class, 'updatePriceUsingFifo']);
Route::get('/getOrderOutTransactions', [TestController7::class, 'getOrderOutTransactions']);
Route::get('/fixOrderPrices', [TestController7::class, 'fixOrderPrices']);
Route::get('/getOverConsumedSuppliesReport', [TestController7::class, 'getOverConsumedSuppliesReport']);

Route::get('/updateUnitPrices', [TestController7::class, 'updateUnitPrices']);

Route::get('/getComponentsData', [TestController7::class, 'getComponentsData']);
Route::get('/fixInventoryForReadyOrder/{orderId}', [FixOrderWithFifoController::class, 'fixInventoryForReadyOrder']);
Route::get('/getAllocationsPreview/{orderId}', [FixOrderWithFifoController::class, 'getAllocationsPreview']);


Route::get('/testJobAllocationOut', [TestController8::class, 'testJobAllocationOut']);

Route::get('/phpinfo', function () {
    return phpinfo();
});


// لتحديث GRN واحد
Route::get('syncSingleGrnPrices/{grnId}', [TestController8::class, 'syncSingleGrnPrices']);

// لتحديث جميع GRNs
Route::get('/syncAllGrns', [TestController8::class, 'syncAllGrns']);
Route::get('/productPriceHistory', [ProductPriceHistoryController::class, 'index']);
Route::get('/manufacturingProductPriceHistory', [ProductPriceHistoryController::class, 'manufacturingProductPriceHistory']);
Route::get('/updateAllManufacturedPrices', [ProductPriceHistoryController::class, 'updateAllManufacturedPrices']);
Route::get('/getSuppliesManufacturedProducts', [TestController8::class, 'getSuppliesManufacturedProducts']);

Route::get('/getSuppliesManufacturedProducts2', [TestController8::class, 'getSuppliesManufacturedProducts2']);

Route::get('/getAllRawMaterialInTransactionsByStore', [TestController8::class, 'getAllRawMaterialInTransactionsByStore']);

Route::get('/runBackfill', [TestController8::class, 'runBackfill']);
Route::get('/getNewReport', [TestController8::class, 'getNewReport']);

Route::get('/handleCopy', [CopyOrderOutToBranchStoreController::class, 'handle']);

Route::get('/wrongStoreReport', [TestController8::class, 'wrongStoreReport']);
Route::get('/updatePricesOfSuppliesManufacturingProducts', [TestController8::class, 'updatePricesOfSuppliesManufacturingProducts']);;

Route::get('/runFullUpdate/{categoryId}/{unitId}/{newPrice}', [TestController8::class, 'handleUpdateFromRoute']);

Route::get('/updateCorrectStore',function(){

    DB::statement("
        UPDATE stock_supply_orders sso
        JOIN stock_supply_order_details ssod ON ssod.stock_supply_order_id = sso.id
        JOIN products p ON p.id = ssod.product_id
        SET sso.store_id = 8
        WHERE p.category_id = 31
    ");

    DB::statement("
        UPDATE stock_issue_orders sio
        JOIN stock_issue_order_details siod ON siod.stock_issue_order_id = sio.id
        JOIN products p ON p.id = siod.product_id
        SET sio.store_id = 8
        WHERE p.category_id = 31
    ");

    DB::statement("
        UPDATE stock_adjustment_details sad
        JOIN products p ON p.id = sad.product_id
        SET sad.store_id = 8
        WHERE p.category_id = 31
    ");

    // ----------------------------
    
    DB::statement("
        UPDATE stock_supply_orders sso
        JOIN stock_supply_order_details ssod ON ssod.stock_supply_order_id = sso.id
        JOIN products p ON p.id = ssod.product_id
        SET sso.store_id = 9
        WHERE p.category_id = 36
    ");

    DB::statement("
        UPDATE stock_issue_orders sio
        JOIN stock_issue_order_details siod ON siod.stock_issue_order_id = sio.id
        JOIN products p ON p.id = siod.product_id
        SET sio.store_id = 9
        WHERE p.category_id = 36
    ");

    DB::statement("
        UPDATE stock_adjustment_details sad
        JOIN products p ON p.id = sad.product_id
        SET sad.store_id = 9
        WHERE p.category_id = 36
    ");;
});