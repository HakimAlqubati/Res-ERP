<?php

namespace App\Modules\Stock\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Reports\ProductGrnAggregation\Services\ProductAggregationReportService;
use Illuminate\Http\Request;

class ProductGrnAggregationReportController extends Controller
{
    public function __construct(
        private readonly ProductAggregationReportService $reportService
    ) {}

    /**
     * Display the Product-Level GRN Aggregation Report.
     */
    public function index(Request $request)
    {
        // Allow smart filtering
        $filters = $request->only(['search', 'date_from', 'date_to', 'store_id', 'exclude_completed']);
        
        // Fetch paginated report (15 items per page)
        $report = $this->reportService->getReport($filters, 15);

        // Load the view
        return view('stock::reports.product-grn-aggregation.index', compact('report', 'filters'));
    }
}
