<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Inventory\FifoInventoryDetailReportService;
use App\Services\Inventory\InboundOutflowReportService;

class FifoInventoryReportController extends Controller
{
    protected FifoInventoryDetailReportService $reportService;

    public function __construct(FifoInventoryDetailReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        $showSmallestUnit = $request->input('smallest_unit', false);

        $report = $this->reportService->getDetailedRemainingStock(
            $validated['product_id'],
            $validated['store_id'],
            $showSmallestUnit
        );

        return response()->json([
            'data' => $report,
            'status' => 'success',
        ]);
    }

    public function inboundOutflowReport(Request $request)
    {
        $validated = $request->validate([
            'transactionable_id' => 'required|integer',
            'transactionable_type' => 'nullable|string',
        ]);

        $transactionableId = $validated['transactionable_id'];
        $transactionableType = $validated['transactionable_type'] ?? null;
        $reportService = new InboundOutflowReportService();
        $report = $reportService->generate($transactionableId, $transactionableType);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
