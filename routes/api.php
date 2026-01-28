<?php

use App\Http\Controllers\API\FifoInventoryReportController;
use App\Http\Controllers\Api\InventoryDashboardController;
use App\Http\Controllers\Api\Inventory\OptimizedInventoryController;
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
    Route::get('/v2/productOrderQuantities', [ProductController::class, 'getProductOrderQuantitiesV2']);
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
    dd(count($productIds), count($productIds2));
    dd(round(0.999600, 1));
    return User::role([1, 3])->pluck('id');
});


Route::get('/inventoryDashboardTest', [InventoryDashboardController::class, 'getSummary'])
    // ->middleware('auth:api')
;
Route::prefix('v2/inventory')->group(function () {
    // تقرير المخزون الكامل
    Route::get('/report', [OptimizedInventoryController::class, 'report']);

    // تقرير المخزون مع Pagination
    Route::get('/report/paginated', [OptimizedInventoryController::class, 'reportPaginated']);

    // مخزون منتج واحد
    Route::get('/product/{productId}', [OptimizedInventoryController::class, 'productInventory']);

    // الكمية المتبقية لمنتج/وحدة
    Route::get('/remaining-qty', [OptimizedInventoryController::class, 'remainingQty']);

    // المنتجات تحت الحد الأدنى
    Route::get('/low-stock', [OptimizedInventoryController::class, 'lowStock']);

    // حركات منتج (دخول/خروج)
    Route::get('/movements/{productId}', [OptimizedInventoryController::class, 'movements']);
});

// ═══════════════════════════════════════════════════════════════════════════
// V3 Inventory Summary Routes (from inventory_summary table - FAST)
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('v3/inventory')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\Inventory\InventorySummaryController::class, 'index']);
});

Route::get('/testFun', function () {
    return response()->json([
        'success' => true,
        'data' => 'test'
    ]);
});
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

    // ═══════════════════════════════════════════════════════════════════════════
    // V2 Optimized Inventory Routes (OptimizedInventoryService)
    // ═══════════════════════════════════════════════════════════════════════════


    // Financial Category Reporting Routes
    Route::prefix('financial')->group(function () {
        Route::prefix('categories')->group(function () {
            Route::get('report', [App\Http\Controllers\Api\Financial\FinancialCategoryReportController::class, 'report']);
            Route::get('statistics', [App\Http\Controllers\Api\Financial\FinancialCategoryReportController::class, 'statistics']);
            Route::get('summary', [App\Http\Controllers\Api\Financial\FinancialCategoryReportController::class, 'summary']);
            Route::get('trends', [App\Http\Controllers\Api\Financial\FinancialCategoryReportController::class, 'trends']);
            Route::get('comparison', [App\Http\Controllers\Api\Financial\FinancialCategoryReportController::class, 'comparison']);
            Route::get('{id}/details', [App\Http\Controllers\Api\Financial\FinancialCategoryReportController::class, 'categoryDetails']);
        });
        Route::get('income-statement', [App\Http\Controllers\Api\FinancialReportController::class, 'incomeStatement']);
        Route::get('income-statement/multi-branch', [App\Http\Controllers\Api\FinancialReportController::class, 'multiBranchIncomeStatement']);

        // Payroll Financial Sync Routes
        Route::prefix('payroll')->group(function () {
            Route::post('sync/{payrollRunId}', [App\Http\Controllers\Api\Financial\PayrollFinancialSyncController::class, 'syncPayrollRun']);
            Route::post('sync/branch/{branchId}', [App\Http\Controllers\Api\Financial\PayrollFinancialSyncController::class, 'syncBranch']);
            Route::post('sync/all', [App\Http\Controllers\Api\Financial\PayrollFinancialSyncController::class, 'syncAll']);
            Route::get('status/{payrollRunId}', [App\Http\Controllers\Api\Financial\PayrollFinancialSyncController::class, 'getSyncStatus']);
            Route::delete('sync/{payrollRunId}', [App\Http\Controllers\Api\Financial\PayrollFinancialSyncController::class, 'deleteSync']);
            Route::put('sync/{payrollRunId}', [App\Http\Controllers\Api\Financial\PayrollFinancialSyncController::class, 'resync']);
        });
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

    Route::get('/inventoryDashboard', [InventoryDashboardController::class, 'getSummary']);

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

    // New route: list users, optional filter by branch_id
    Route::get('/users', function (Request $request) {
        $branchId = $request->query('branch_id');

        $query = User::query()->select('id', 'name', 'email');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    })->middleware('auth:api');
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

require base_path('routes/ocr.php');
require base_path('routes/custom_api_route_hr.php');
require base_path('routes/custom_route.php');
require base_path('routes/custom_api_route_inventory.php');
require base_path('routes/custom_api_test.php');

// HR Attendance Module Routes (v3) - loaded via AttendanceServiceProvider



// Route::get('/stores', fn() => \App\Models\Store::select('id', 'name')->get());
// Route::get('/products', fn() => \App\Models\Product::select('id', 'name')->get());
// Route::post('/stock-inventory', function(\Illuminate\Http\Request $request) {
// من هنا تحفظ البيانات في جدول StockInventory وتعيد رسالة نجاح
// return response()->json(['message' => 'تم الحفظ بنجاح']);
// (اكتب الكود حسب منطقك)
// });


Route::get('/testBranchStoreIds', function () {
    $storeIds = Branch::branches()->whereNotNull('store_id')->pluck('store_id')->toArray();
    return $storeIds;
});

// Fix Closing Stock transactions: change type from expense to income
Route::get('/fixClosingStockTransactionType', function () {
    try {
        $closingStockCategory = \App\Models\FinancialCategory::findByCode(\App\Enums\FinancialCategoryCode::CLOSING_STOCK);

        if (!$closingStockCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Closing Stock category not found.',
                'affected' => 0,
            ], 404);
        }

        // Include soft-deleted records with withTrashed()
        $affected = \App\Models\FinancialTransaction::withTrashed()
            ->where('category_id', $closingStockCategory->id)
            ->where('type', \App\Models\FinancialCategory::TYPE_EXPENSE)
            ->update(['type' => \App\Models\FinancialCategory::TYPE_INCOME]);

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$affected} closing stock transactions from expense to income.",
            'affected' => $affected,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'affected' => 0,
        ], 500);
    }
});

Route::get('/fixInventoryMovementDates', function () {
    $storeIds = Branch::branches()->whereNotNull('store_id')->pluck('store_id')->toArray();

    if (empty($storeIds)) {
        return response()->json([
            'success' => false,
            'message' => 'No stores found with a valid store_id.',
            'affected' => 0,
        ]);
    }

    try {
        $affected = \App\Models\InventoryTransaction::query()
            ->join('orders', 'inventory_transactions.transactionable_id', '=', 'orders.id')
            ->where('inventory_transactions.transactionable_type', Order::class)
            ->where('inventory_transactions.movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
            ->whereIn('inventory_transactions.store_id', $storeIds)
            ->whereNotNull('orders.transfer_date')
            ->where(function ($query) {
                $query->whereColumn('inventory_transactions.movement_date', '!=', 'orders.transfer_date')
                    ->orWhereColumn('inventory_transactions.transaction_date', '!=', 'orders.transfer_date');
            })
            ->update([
                'inventory_transactions.movement_date'    => DB::raw('orders.transfer_date'),
                'inventory_transactions.transaction_date' => DB::raw('orders.transfer_date'),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$affected} inventory transaction rows.",
            'affected' => $affected,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'affected' => 0,
        ], 500);
    }
});

Route::get('/testEnv', function () {
    // dd('sdf');
    dd(\App\Models\Employee::where('avatar', 'like', '%v0wj67L8hG1pqXq%')->first());
    dd(env('APP_ENV'));
});

Route::get('/testFun', function () {
    return response()->json([
        'success' => true,
        'data' => 'test'
    ]);
});

// API endpoint to get month options based on settings
Route::get('/monthOptions', function () {
    $options = getMonthOptionsBasedOnSettings();
    $result = [];

    foreach ($options as $key => $label) {
        // Parse the month key (e.g., "January 2026") to get year and month
        $date = \Carbon\Carbon::parse($key);
        $endOfMonthData = getEndOfMonthDate($date->year, $date->month);

        $result[] = [
            'key' => $key,
            'label' => $label,
            'start_date' => $endOfMonthData['start_month'],
            'end_date' => $endOfMonthData['end_month'],
        ];
    }

    return response()->json([
        'success' => true,
        'data' => $result,
    ]);
});

// ========================================
// Rekognition Sync Routes
// ========================================

 
 

/**
 * عرض حالة المزامنة - مقارنة Rekognition مع DynamoDB
 * GET /api/rekognition/status
 */
Route::get('/rekognition/status', function () {
    $rekognition = new \Aws\Rekognition\RekognitionClient([
        'region' => env('AWS_DEFAULT_REGION'),
        'version' => 'latest',
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    $dynamoDb = new \Aws\DynamoDb\DynamoDbClient([
        'region' => env('AWS_DEFAULT_REGION'),
        'version' => 'latest',
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    // Get faces from Rekognition
    $collectionId = config('rekognition.collection_id', 'emps');
    $rekognitionFaces = [];
    try {
        $result = $rekognition->listFaces(['CollectionId' => $collectionId]);
        $rekognitionFaces = collect($result['Faces'])->pluck('FaceId')->toArray();
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch Rekognition faces: ' . $e->getMessage(),
        ], 500);
    }

    // Get records from DynamoDB
    $dynamoDbRecords = [];
    try {
        $result = $dynamoDb->scan(['TableName' => 'face_recognition']);
        $dynamoDbRecords = collect($result['Items'])->pluck('RekognitionId.S')->toArray();
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch DynamoDB records: ' . $e->getMessage(),
        ], 500);
    }

    // Compare
    $inRekognitionOnly = array_diff($rekognitionFaces, $dynamoDbRecords);
    $inDynamoDbOnly = array_diff($dynamoDbRecords, $rekognitionFaces);
    $synced = array_intersect($rekognitionFaces, $dynamoDbRecords);

    return response()->json([
        'success' => true,
        'collection_id' => $collectionId,
        'dynamodb_table' => 'face_recognition',
        'summary' => [
            'rekognition_faces' => count($rekognitionFaces),
            'dynamodb_records' => count($dynamoDbRecords),
            'synced' => count($synced),
            'missing_in_dynamodb' => count($inRekognitionOnly),
            'orphaned_in_dynamodb' => count($inDynamoDbOnly),
        ],
        'missing_in_dynamodb' => array_values($inRekognitionOnly),
        'orphaned_in_dynamodb' => array_values($inDynamoDbOnly),
    ]);
});

// ========================================
// Temporary Employee Import API
// ========================================

/**
 * استيراد الموظفين من ملف Excel
 * POST /api/import-employees
 * 
 * @param file - ملف Excel (xlsx, xls, csv)
 */
Route::post('/import-employees', function (Request $request) {
    // التحقق من وجود الملف
    if (!$request->hasFile('file')) {
        return response()->json([
            'success' => false,
            'message' => 'No file uploaded. Please upload an Excel file.',
        ], 400);
    }

    $file = $request->file('file');

    // التحقق من نوع الملف
    $allowedExtensions = ['xlsx', 'xls', 'csv'];
    $extension = strtolower($file->getClientOriginalExtension());

    if (!in_array($extension, $allowedExtensions)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid file type. Allowed types: xlsx, xls, csv',
        ], 400);
    }

    try {
        $import = new \App\Imports\EmployeeImport();

        \Maatwebsite\Excel\Facades\Excel::import($import, $file);

        $successCount = $import->getSuccessfulImportsCount();
        $existingCount = $import->getExistingCount();
        $failedCount = $import->getErrorCount();
        $totalCount = $import->getProcessedRowsCount() + $failedCount;

        return response()->json([
            'success' => true,
            'message' => "Import completed successfully.",
            'data' => [
                'total_count' => $totalCount,
                'success_count' => $successCount,
                'existing_count' => $existingCount,
                'failed_count' => $failedCount,
                'has_errors' => $import->hasErrors(),
                'errors' => $import->getImportErrors(),
            ],
        ]);
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
        $errorMessages = [];

        foreach ($failures as $failure) {
            $errorMessages[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ];
        }

        return response()->json([
            'success' => false,
            'message' => 'Validation errors during import',
            'errors' => $errorMessages,
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], 500);
    }
});

/**
 * تحميل ملف Excel نموذجي للموظفين
 * GET /api/import-employees/template
 */
Route::get('/import-employees/template', function () {
    $templatePath = public_path('data/sample_file_imports/Sample import file.xlsx');

    if (!file_exists($templatePath)) {
        return response()->json([
            'success' => false,
            'message' => 'Template file not found.',
        ], 404);
    }

    return response()->download($templatePath, 'employee_import_template.xlsx');
});
