<?php

use App\Http\Controllers\API\FifoInventoryReportController;
use App\Http\Controllers\Api\InventoryDashboardController;
use App\Http\Controllers\Api\ManufacturingInventoryReportController;
use App\Http\Controllers\Api\ManufacturingReportController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\PurchaseReportController;
use App\Http\Controllers\Api\Reports\BranchConsumptionController;
use App\Http\Controllers\Api\Reports\ResellerReportController;
use App\Http\Controllers\Api\Reports\StockAdjustmentReportController;
use App\Http\Controllers\Api\Reports\StoreCostReportController;
use App\Http\Controllers\Api\ReturnedOrderController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TestController3;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use App\Services\MultiProductsInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/compare', function (Request $request) {
    $qty        = $request->input('qty');
    $product_id = $request->input('product_id');
    $unit_id    = $request->input('unit_id');
    // $fdata = getSumQtyOfProductFromPurchases($product_id, $unit_id);
    $fdata = comparePurchasedWithOrderdQties($product_id, $unit_id);

    return $fdata;
});

Route::post('/login', [AuthController::class, 'login'])
// ->middleware(EnsureOwnerIfRequired::class)
;
Route::post('/login/otp/check', [AuthController::class, 'loginWithOtp']);
Route::get('/products', [ProductController::class, 'index'])->middleware('lastSeen');
Route::get('/orders/{order}/pdf', [OrderController::class, 'generate']);

Route::middleware(['auth:api', 'lastSeen'])->group(function () {
    Route::get('/report_products', [ProductController::class, 'reportProducts']);
    Route::get('/user', [AuthController::class, 'getCurrnetUser']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('orders', OrderController::class);
    Route::post('orders2', [OrderController::class, 'index']);
    Route::post('orders2/{id}', [OrderController::class, 'index']);
    Route::post('orders2/update/{id}', [OrderController::class, 'update']);
    Route::resource('orderDetails', OrderDetailsController::class);
    Route::patch('patch', [OrderDetailsController::class, 'update']);
    Route::post('patch2', [OrderDetailsController::class, 'update']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/v2/report_products', [ProductController::class, 'reportProductsv2']);
    Route::get('/v2/report_products/details/{category_id}', [ProductController::class, 'reportProductsv2Details']);
    Route::get('/getProductOrderQuantities', [ProductController::class, 'getProductOrderQuantities']);
});

Route::post('/user/updateBranch', [AuthController::class, 'updateBranch'])
    ->middleware('auth:api');

Route::get('/test', function () {

// بناء كويري واحد يحتوي كل المصادر باستخدام union
    $productIds = DB::table('orders_details as od')
        ->join('orders as o', 'od.order_id', '=', 'o.id')
        ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
        ->whereNull('o.deleted_at')
        ->select('od.product_id')

        ->union(

            DB::table('stock_issue_order_details as sid')
                ->join('stock_issue_orders as si', 'sid.stock_issue_order_id', '=', 'si.id')
                ->whereNull('si.deleted_at')
                ->select('sid.product_id')
        )

        ->union(

            DB::table('stock_adjustment_details')
                ->where('adjustment_type', 'decrease')
                ->select('product_id')
        )

        ->distinct()
        ->pluck('product_id');

        $productIds1 = DB::table('orders_details as od')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('o.deleted_at')
            ->distinct()
            ->pluck('od.product_id');

        $productIdsFromOrders = DB::table('orders_details as od')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('o.deleted_at')
            ->pluck('od.product_id');

        $productIdsFromIssues = DB::table('stock_issue_order_details as sid')
            ->join('stock_issue_orders as si', 'sid.stock_issue_order_id', '=', 'si.id')
            ->whereNull('si.deleted_at')
            ->pluck('sid.product_id');

        $productIdsFromAdjustments = DB::table('stock_adjustment_details')
            ->where('adjustment_type', 'decrease')
            ->pluck('product_id');
               $productIds2 = $productIdsFromOrders
            ->merge($productIdsFromIssues)
            ->merge($productIdsFromAdjustments)
            ->unique()
            ->sort()
            ->values();
    dd(count($productIds),count($productIds2));
    dd(round(0.999600, 1));
    return User::role([1, 3])->pluck('id');
});


Route::get('/inventoryDashboardTest', [InventoryDashboardController::class, 'getSummary'])
// ->middleware('auth:api')
;
Route::middleware('auth:api')->group(function () {
    Route::put('updateFcmToken', [FcmController::class, 'updateDeviceToken']);

    // Inventory Routes
    Route::prefix('inventory')->group(function () {
        Route::apiResource('transactions', App\Http\Controllers\Api\InventoryTransactionController::class);
        Route::apiResource('stockSupplyOrder', App\Http\Controllers\Api\StockSupplyOrderController::class);
        Route::get('/minimumStockReport', [App\Http\Controllers\Api\InventoryReportController::class, 'minimumStockReport']);
        Route::get('/inventoryReport', [App\Http\Controllers\Api\InventoryReportController::class, 'inventoryReport']);
        Route::get('/filters', [App\Http\Controllers\Api\InventoryReportController::class, 'filters']);
        Route::get('/productTracking', [App\Http\Controllers\Api\InventoryReportController::class, 'productTracking']);
    });
});

Route::middleware('auth:api')->group(function () {
Route::get('purchaseReports', [PurchaseReportController::class, 'index']);
Route::prefix('returnedOrders')->group(function () {
    Route::get('/', [ReturnedOrderController::class, 'index']);    // all with filters
    Route::get('/{id}', [ReturnedOrderController::class, 'show']); // single order
});

Route::get('/fifoInventoryReport', [FifoInventoryReportController::class, 'show']);
Route::get('/inboundOutflowReport', [FifoInventoryReportController::class, 'inboundOutflowReport']);
Route::get('/purchaseInvoices', [PurchaseInvoiceController::class, 'index']);
Route::get('/manufacturingReport', [ManufacturingReportController::class, 'index']);
Route::get('/manufacturingInventoryReport', [ManufacturingInventoryReportController::class, 'show']);

Route::get('/inventoryDashboard', [InventoryDashboardController::class, 'getSummary'])
;

Route::prefix('reseller')->group(function () {
    Route::get('branchSalesBalanceReport', [ResellerReportController::class, 'branchSalesBalanceReport']);
    Route::get('orderDeliveryReports', [ResellerReportController::class, 'orderDeliveryReports']);
});

Route::get('stockAdjustmentsByCategory', [StockAdjustmentReportController::class, 'byCategory']);

Route::get('/storeCostReport', [StoreCostReportController::class, 'generate']);
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::get('/minimumStockReportToSupply', [App\Http\Controllers\Api\InventoryReportController::class, 'minimumStockReportToSupply']);
Route::get('/branchQuantities', [App\Http\Controllers\Api\InventoryReportController::class, 'branchQuantities']);
Route::get('/testInventoryReport', [App\Http\Controllers\Api\InventoryReportController::class, 'inventoryReport']);
Route::get('/testInventoryPurchasedReport', [App\Http\Controllers\Api\InventoryReportController::class, 'testInventoryPurchasedReport']);
Route::get('/testInventoryReport2', function (Request $request) {
    $productId        = $request->input('product_id');
    $unitId           = $request->input('unit_id');
    $storeId          = $request->input('store_id');
    $inventoryService = new MultiProductsInventoryService(
        null,
        $productId,
        $unitId,
        $storeId
    );
    $targetUnit = \App\Models\UnitPrice::where('product_id', $productId)
    ->where('unit_id', $unitId)->with('unit')
        ->first();
        $inventoryReportProduct = $inventoryService->getInventoryForProduct($productId);
        $inventoryRemainingQty  = collect($inventoryReportProduct)->firstWhere('unit_id', $unitId)['remaining_qty'] ?? 0;
        return response()->json($inventoryRemainingQty);
    });
    
    Route::get('/branches', function () {
        return Branch::active()
        ->branches()
        ->get(['id', 'name', 'type'])
        
        ->makeHidden([
            'categories',
            'salesAmounts',
            'paidAmounts',
            'total_orders_amount',
            'customized_categories',
            'orders_count',
            'reseller_balance',
            'total_paid',
            'total_sales',
        ]);
    });
    
});
Route::get('/sendFCM', [TestController3::class, 'sendFCM']);
Route::get('productsSearch', function (\Illuminate\Http\Request $request) {
    $query = $request->query('query', '');

    return \App\Models\Product::where('name', 'like', "%{$query}%")
        ->orWhere('code', 'like', "%{$query}%")
        ->limit(20)
        ->get(['id', 'name', 'code']);
});

Route::get('branchConsumptionReport', [BranchConsumptionController::class, 'index']);
Route::get('branchConsumptionReport/topBranches', [BranchConsumptionController::class, 'topBranches']);
Route::get('branchConsumptionReport/topProducts', [BranchConsumptionController::class, 'topProducts']);

Route::get('/app/settings', [SettingController::class, 'show']);
 
require base_path('routes/custom_api_route_hr.php');
require base_path('routes/custom_route.php');

// Route::get('/stores', fn() => \App\Models\Store::select('id', 'name')->get());
// Route::get('/products', fn() => \App\Models\Product::select('id', 'name')->get());
// Route::post('/stock-inventory', function(\Illuminate\Http\Request $request) {
    // من هنا تحفظ البيانات في جدول StockInventory وتعيد رسالة نجاح
    // return response()->json(['message' => 'تم الحفظ بنجاح']);
    // (اكتب الكود حسب منطقك)
// });