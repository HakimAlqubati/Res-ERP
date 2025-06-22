<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ResellerBranches\OrderSalesPaymentsReportService;
use Illuminate\Http\Request;

class OrderSalesPaymentsReportController extends Controller
{
    /**
     * Display the sales and payments report by branch.
     */
    public function index(Request $request, OrderSalesPaymentsReportService $reportService)
    {
        // Generate report data from the service
        $report = $reportService->generate();

        // Pass it to the Blade view
        return view('reports.sales-payments-report', [
            'report' => $report,
        ]);
    }
}
