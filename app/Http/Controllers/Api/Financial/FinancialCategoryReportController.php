<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\CategoryTransactionSummaryResource;
use App\Http\Resources\Financial\FinancialCategoryReportResource;
use App\Http\Resources\Financial\FinancialCategoryStatisticsResource;
use App\Services\Financial\Filters\FinancialCategoryReportFilter;
use App\Services\Financial\Reports\FinancialCategoryReportService;
use App\Services\Financial\Statistics\FinancialCategoryStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinancialCategoryReportController extends Controller
{
    /**
     * Get comprehensive financial category report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function report(Request $request): JsonResponse
    {
        $filter = new FinancialCategoryReportFilter($request->all());
        $service = new FinancialCategoryReportService($filter);
        
        $report = $service->generateReport();
        
        return response()->json(
            new FinancialCategoryReportResource($report->toArray()),
            200
        );
    }

    /**
     * Get detailed statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $filter = new FinancialCategoryReportFilter($request->all());
        $service = new FinancialCategoryStatisticsService($filter);
        
        $statistics = $service->generateStatistics();
        
        return response()->json(
            new FinancialCategoryStatisticsResource($statistics->toArray()),
            200
        );
    }

    /**
     * Get quick summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $filter = new FinancialCategoryReportFilter($request->all());
        $service = new FinancialCategoryStatisticsService($filter);
        
        $summary = $service->getQuickSummary();
        
        return response()->json([
            'success' => true,
            'data' => $summary,
        ], 200);
    }

    /**
     * Get trend analysis
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trends(Request $request): JsonResponse
    {
        $filter = new FinancialCategoryReportFilter($request->all());
        $service = new FinancialCategoryReportService($filter);
        
        $trends = $service->generateTrendReport();
        
        return response()->json([
            'success' => true,
            'data' => $trends,
        ], 200);
    }

    /**
     * Get comparison between two periods
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comparison(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_one' => 'required|array',
            'period_one.start_date' => 'required|date',
            'period_one.end_date' => 'required|date|after_or_equal:period_one.start_date',
            'period_two' => 'required|array',
            'period_two.start_date' => 'required|date',
            'period_two.end_date' => 'required|date|after_or_equal:period_two.start_date',
        ]);

        $periodOneFilter = new FinancialCategoryReportFilter(
            array_merge($request->except(['period_one', 'period_two']), $validated['period_one'])
        );
        
        $periodTwoFilter = new FinancialCategoryReportFilter(
            array_merge($request->except(['period_one', 'period_two']), $validated['period_two'])
        );

        $service = new FinancialCategoryReportService($periodOneFilter);
        $comparison = $service->generateComparisonReport($periodOneFilter, $periodTwoFilter);
        
        return response()->json([
            'success' => true,
            'data' => $comparison,
        ], 200);
    }

    /**
     * Get detailed report for a specific category
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function categoryDetails(Request $request, int $id): JsonResponse
    {
        $filter = new FinancialCategoryReportFilter($request->all());
        $service = new FinancialCategoryReportService($filter);
        
        $details = $service->getCategoryDetails($id);
        
        if (empty($details)) {
            return response()->json([
                'success' => false,
                'message' => 'No transactions found for this category with the given filters',
                'data' => null,
            ], 200);
        }
        
        return response()->json([
            'success' => true,
            'data' => new CategoryTransactionSummaryResource($details),
        ], 200);
    }
}
