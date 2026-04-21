<?php

namespace App\Modules\HR\PayrollReports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\PayrollReports\Contracts\PayrollReportServiceInterface;
use App\Modules\HR\PayrollReports\DTOs\PayrollReportFilterDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollReportController extends Controller
{
    /**
     * Retrieve and aggregate the comprehensive payroll report.
     *
     * @param Request $request
     * @param PayrollReportServiceInterface $reportService
     * @return \App\Modules\HR\PayrollReports\DTOs\PayrollReportResultDTO
     */
    public function getReport(Request $request, PayrollReportServiceInterface $reportService)
    {
        $filter = PayrollReportFilterDTO::fromRequest($request);

        return $reportService->generate($filter);
    }
}
