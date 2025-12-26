<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Services\Docs\FinancialHRReportService;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for displaying HR and Financial integration documentation.
 */
class FinancialHRReportController extends Controller
{
    public function __construct(
        protected FinancialHRReportService $reportService
    ) {}

    /**
     * Display the financial HR integration report page.
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect('/admin/login');
        }

        $data = $this->reportService->getReportData();

        return view('docs.financial-hr-report', $data);
    }
}
