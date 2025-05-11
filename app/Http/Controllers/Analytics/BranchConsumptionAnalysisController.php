<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\BranchConsumptionAnalysisService;
use Illuminate\Http\Request;

class BranchConsumptionAnalysisController extends Controller
{
    protected BranchConsumptionAnalysisService $analysisService;

    public function __construct(BranchConsumptionAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    public function analyze(Request $request)
    {

        // تعريف المتغيرات من المدخلات
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $productIds = $request->query('product_ids') ?? [];
        $categoryIds = $request->query('category_ids') ?? [];
        $branchIds = $request->query('branch_ids') ?? [];

        $data = $this->analysisService->getBranchConsumption(
            $productIds,
            $categoryIds,
            $branchIds,
            $startDate,
            $endDate
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function compare(Request $request)
    {


        // استخراج المتغيرات
        $productIds = $request->query('product_ids') ?? [];
        $categoryIds = $request->query('category_ids') ?? [];
        $branchIds = $request->query('branch_ids') ?? [];

        $period1Start = $request->query('period1_start');
        $period1End = $request->query('period1_end');
        $period2Start = $request->query('period2_start');
        $period2End = $request->query('period2_end');

        // تنفيذ المقارنة
        $data = $this->analysisService->compareTwoPeriods(
            $productIds,
            $categoryIds,
            $branchIds,
            $period1Start,
            $period1End,
            $period2Start,
            $period2End
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
