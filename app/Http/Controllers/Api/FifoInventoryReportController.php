<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Inventory\FifoInventoryDetailReportService;

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

        $report = $this->reportService->getDetailedRemainingStock(
            $validated['product_id'],
            $validated['store_id']
        );

        return response()->json([
            'data' => $report,
            'status' => 'success',
        ]);
    }
}
