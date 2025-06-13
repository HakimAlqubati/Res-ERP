<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\StockIssueOrder;
use App\Models\UnitPrice;
use App\Services\BulkPricingAdjustmentService;
use App\Services\FifoMethodService;
use App\Services\GrnPriceSyncService;
use App\Services\ManufacturingBackfillService;
use App\Services\ProductSupplyPriceUpdaterService;
use App\Services\Reports\InventoryWithUsageReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestController8 extends Controller
{


    public function testJobAllocationOut(Request $request)
    {
        $transactions = $this->getMergedAndSortedTransactions();
        $allocations = [];
        foreach ($transactions as $trx) {
            $model = $trx['model'];
            $details = $trx['details'];
            $date = $trx['date'];
            $type = $trx['type'];

            foreach ($details as $detail) {
                $fifoService = new FifoMethodService($model);

                $requestedQty = get_class($model) == Order::class
                    ? $detail->available_quantity
                    : $detail->quantity;
                $allocations[] = $fifoService->getAllocateFifo(
                    $detail->product_id,
                    $detail->unit_id,
                    $requestedQty,
                    $type

                );
            }
            dd($allocations);
        }
        return $allocations;
    }
    protected function getMergedAndSortedTransactions(): \Illuminate\Support\Collection
    {
        $merged = collect();

        // أوامر الصرف
        $stockIssues = StockIssueOrder::with('details')->limit(2)->get();
        foreach ($stockIssues as $issue) {
            $merged->push([
                'type' => 'stock_issue',
                'model' => $issue,
                'details' => $issue->details,
                'date' => $issue->order_date,
            ]);
        }

        // الطلبات
        $orders = Order::whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])

            ->with(['orderDetails', 'logs' => function ($q) {
                $q->where('log_type', 'change_status')
                    ->where('new_status', Order::READY_FOR_DELEVIRY);
            }])->limit(20)
            ->get();

        foreach ($orders as $order) {
            $log = $order->logs->sortByDesc('created_at')->first();


            if (!$log) {
                continue; // تخطي إذا لم يكن هناك log
            }

            $merged->push([
                'type' => 'order',
                'model' => $order,
                'details' => $order->orderDetails,
                'date' => $log->created_at,
            ]);
        }

        return $merged->sortBy('date')->values();
    }

    // ✅ تحديث GRN واحد فقط بناءً على ID
    public function syncSingleGrnPrices($grnId, GrnPriceSyncService $service)
    {
        try {
            $service->syncPricesFromInvoice($grnId);
            return response()->json([
                'status' => 'success',
                'message' => "✅ GRN #$grnId prices synced successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => '❌ Error syncing GRN prices.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ تحديث كل GRNs دفعة واحدة
    public function syncAllGrns(GrnPriceSyncService $service)
    {
        try {
            $service->syncAllGrnPrices();
            return response()->json([
                'status' => 'success',
                'message' => '✅ All GRNs synced successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => '❌ Error syncing all GRNs.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function getSuppliesManufacturedProducts(Request $request)
    {
        $service =  new ManufacturingBackfillService();
        $transactions = $service->simulateBackfill($request->get('store_id'));

        return $transactions;
    }
    public function storeSuppliesManufacturedProducts(Request $request)
    {
        if (!$request->has('store_id')) {
            return response()->json([
                'status' => 'error',
                'message' => '❌ Store ID is required.',
            ], 400);
        }
        $service =  new ManufacturingBackfillService();
        $transactions = $service->handleFromSimulation($request->get('store_id'));
        return $transactions;
    }
    public function getNewReport(Request $request)
    {
        $storeId = $request->get('store_id');
        $service = new InventoryWithUsageReportService($storeId);
        $reportData = $service->getReport();
        return response()->json(
            $reportData,
        );
    }

    public function wrongStoreReport(\App\Services\WrongStoreProductReportService $reportService)
    {
        $movementType = request()->get('movement_type', 'in'); // 'in' or 'out'
        $report = $reportService->getReport($movementType);
        return view('reports.wrong-store-products', compact('report'));
        return $report;
    }

    public function updatePricesOfSuppliesManufacturingProducts(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = $validated['product_id'];

        // استدعاء خدمة التحديث
        $result = ProductSupplyPriceUpdaterService::updateSupplyPrice($productId);

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
        ]);
    }

    // داخل ProductController.php

    /**
     * هذه الدالة تستقبل المتغيرات من الـ Route
     * وتستدعي دالة التحديث الرئيسية.
     */
    public function handleUpdateFromRoute(int $categoryId, int $unitId, float $oldPrice, float $newPrice)
    {
        $service = new BulkPricingAdjustmentService();

        return $service->updateAllHistoricalPrices($categoryId, $unitId, $oldPrice, $newPrice);
    }
}
