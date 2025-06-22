<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ResellerBranches\OrderDeliveryReportService;
use Illuminate\Http\Request;

class OrderDeliveryReportController extends Controller
{
    /**
     * عرض تقرير التسليم والفوترة للعملاء
     */
    public function index(Request $request, OrderDeliveryReportService $reportService)
    {
        // توليد البيانات من الخدمة
        $report = $reportService->generate();

        // عرض صفحة التقرير
        return view('reports.order-delivery', [
            'report' => $report,
        ]);
    }
}
