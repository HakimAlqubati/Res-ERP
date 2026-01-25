<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\StockSupply\Reports\ManufacturingProductLabelReportsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ManufacturingProductLabelReportController extends Controller
{
    protected $reportService;

    public function __construct(ManufacturingProductLabelReportsService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get manufacturing product labels data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLabels(Request $request): JsonResponse
    {
        $request->validate([
            'stock_supply_order_id' => 'nullable|integer|exists:stock_supply_orders,id',
            'product_id' => 'nullable|integer',
            'store_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filters = $request->only([
            'stock_supply_order_id',
            'product_id',
            'store_id',
            'category_id',
            'from_date',
            'to_date'
        ]);

        $perPage = $request->input('per_page', 20);

        $result = $this->reportService->getLabelsReport($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function getLabelDetails(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'batch_code' => 'required|string|size:8', // Expecting Ymd format (8 chars)
        ]);

        $productId = $request->input('product_id');
        $batchCode = $request->input('batch_code');

        $data = $this->reportService->getLabelDetails($productId, $batchCode);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Label details not found for the given product and batch.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
