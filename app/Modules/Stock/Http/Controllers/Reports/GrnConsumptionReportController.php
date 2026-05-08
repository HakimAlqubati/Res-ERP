<?php

namespace App\Modules\Stock\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Reports\GrnConsumption\Services\GrnConsumptionReportService;
use Illuminate\Http\Request;

class GrnConsumptionReportController extends Controller
{
    public function __construct(
        private readonly GrnConsumptionReportService $reportService
    ) {}

    /**
     * Display the GRN Consumption Report.
     */
    public function index(Request $request)
    {
        // Allow filtering by GRN Number
        $filters = $request->only([
            'grn_number', 'search', 'date_from', 'date_to', 
            'store_id', 'supplier_id', 'has_invoice', 
            'has_attachment', 'has_notes', 'status', 
            'product_id', 'older_than_days', 'exclude_completed'
        ]);
        
        // Fetch paginated report (15 items per page)
        $report = $this->reportService->getReport($filters, 15);

        // Load the view from the stock module's views directory
        return view('stock::reports.grn-consumption.index', compact('report', 'filters'));
    }

    /**
     * Display the flattened GRN Consumption Report (Products as rows).
     */
    public function flattenedIndex(Request $request)
    {
        // Allow all smart filters
        $filters = $request->only([
            'search', 'grn_number', 'date_from', 'date_to', 
            'store_id', 'supplier_id', 'has_invoice', 
            'has_attachment', 'has_notes', 'status', 
            'product_id', 'older_than_days', 'exclude_completed'
        ]);
        
        $report = $this->reportService->getFlattenedReport($filters, 15);
dd($report);
        return view('stock::reports.grn-consumption.flattened', compact('report', 'filters'));
    }
}
