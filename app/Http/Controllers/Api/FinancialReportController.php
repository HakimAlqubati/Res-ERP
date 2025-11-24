<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Http\Controllers\Controller;
use App\Services\Financial\FinancialReportService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $financialReportService
    ) {}

    public function incomeStatement(Request $request)
    {
        $dto = IncomeStatementRequestDTO::fromRequest($request);

        $data = $this->financialReportService->getIncomeStatement($dto);

        return response()->json($data);
    }
}
