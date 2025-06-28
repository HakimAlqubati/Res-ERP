<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Inventory\StockAdjustmentByCategoryReportService;

class StockAdjustmentReportController extends Controller
{
    protected $reportService;

    public function __construct(StockAdjustmentByCategoryReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function byCategory(Request $request)
    {
        $validated = $request->validate([
            'adjustment_type' => 'nullable|in:increase,decrease,equal',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'store_id' => 'nullable|exists:stores,id',
            'category_ids.*' => 'integer|exists:categories,id',
            'with_details' => 'nullable|boolean',
        ]);

        $report = $this->reportService->generate(
            adjustmentType: $validated['adjustment_type'] ?? null,
            fromDate: $validated['from_date'] ?? null,
            toDate: $validated['to_date'] ?? null,
            storeId: $validated['store_id'] ?? null,
            categoryIds: $validated['category_ids'] ?? null,
            withDetails: $validated['with_details'] ?? false,
        );

        return response()->json([
            'status' => true,
            'data' => $report,
        ]);
    }
}