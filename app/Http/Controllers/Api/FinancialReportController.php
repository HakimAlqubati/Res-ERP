<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Http\Controllers\Controller;
use App\Services\Financial\FinancialReportService;
use App\Services\Financial\MultiBranchFinancialReportService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $financialReportService,
        protected MultiBranchFinancialReportService $multiBranchService
    ) {}

    public function incomeStatement(Request $request)
    {
        $dto = IncomeStatementRequestDTO::fromRequest($request);

        $data = $this->financialReportService->getIncomeStatement($dto);

        return response()->json($data);
    }

    /**
     * Get income statement comparison for multiple branches.
     * 
     * Query Parameters:
     * - branch_ids: array|string (comma-separated) - Required. Branch IDs to compare
     * - start_date: string (Y-m-d) - Optional. Start date for the report
     * - end_date: string (Y-m-d) - Optional. End date for the report
     * - format: string (full|table) - Optional. Response format (default: full)
     * 
     * Example: /api/financial/income-statement/multi-branch?branch_ids=1,2,3&start_date=2025-01-01&end_date=2025-01-31
     */
    public function multiBranchIncomeStatement(Request $request)
    {
        $request->validate([
            'branch_ids' => 'required',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:full,table',
        ]);

        // Parse branch_ids (support comma-separated string or array)
        $branchIds = $request->input('branch_ids');
        if (is_string($branchIds)) {
            $branchIds = array_filter(array_map('trim', explode(',', $branchIds)));
        }
        $branchIds = array_map('intval', $branchIds);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = $request->input('format', 'full');

        if ($format === 'table') {
            $data = $this->multiBranchService->getComparisonTable(
                $branchIds,
                $startDate,
                $endDate
            );
        } else {
            $data = $this->multiBranchService->getMultiBranchIncomeStatement(
                $branchIds,
                $startDate,
                $endDate
            );
        }

        return response()->json($data);
    }
}
