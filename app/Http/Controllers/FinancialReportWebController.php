<?php

namespace App\Http\Controllers;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Services\Financial\FinancialReportService;
use Illuminate\Http\Request;

class FinancialReportWebController extends Controller
{
    public function __construct(
        protected FinancialReportService $financialReportService
    ) {}

    public function index(Request $request)
    {
        $dto = IncomeStatementRequestDTO::fromRequest($request);

        $reportData = $this->financialReportService->getIncomeStatement($dto);

        return view('financial-reports.income-statement', [
            'report' => $reportData,
            'startDate' => $dto->startDate,
            'endDate' => $dto->endDate,
        ]);
    }
}
