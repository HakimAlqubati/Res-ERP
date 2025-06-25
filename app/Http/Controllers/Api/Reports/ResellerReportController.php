<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller; 
use App\Services\Reports\ResellerBranches\BranchSalesBalanceReportService;
use Illuminate\Http\JsonResponse;

class ResellerReportController extends Controller
{
   

    public function branchSalesBalanceReport(BranchSalesBalanceReportService $service): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $service->generate(),
        ]);
    }
}