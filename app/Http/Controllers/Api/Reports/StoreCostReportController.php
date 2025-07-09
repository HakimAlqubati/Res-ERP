<?php
namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\InventoryReports\StoreCostReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreCostReportController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id'   => 'required|integer|exists:stores,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'from_date'  => 'required|date',
            'to_date'    => 'required|date|after_or_equal:from_date',
        ]);

        $report = new StoreCostReportService(
            storeId: $validated['store_id'],
            productId: $validated['product_id'] ?? null,

            fromDate: $validated['from_date'],
            toDate: $validated['to_date'],
            returnableTypes: [
                // هنا تضع أنواع الحركات التي تعتبر مرتجع من الفرع مثل:
                \App\Models\ReturnedOrder::class,
                \App\Models\Order::class,
                \App\Models\StockAdjustmentDetail::class,
                \App\Models\StockIssueOrder::class,
            ]
        );

        $result = $report->generate();

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}