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
        $filters = $request->only(['grn_number']);
        
        // Fetch paginated report (15 items per page)
        $report = $this->reportService->getReport($filters, 15);

        // Load the view from the stock module's views directory
        return view('stock::reports.grn-consumption.index', compact('report', 'filters'));
    }
}
