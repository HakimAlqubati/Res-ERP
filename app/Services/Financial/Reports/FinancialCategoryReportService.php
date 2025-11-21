<?php

namespace App\Services\Financial\Reports;

use App\DTOs\Financial\CategoryTransactionSummaryDTO;
use App\DTOs\Financial\FinancialCategoryReportDTO;
use App\Services\Financial\Aggregators\FinancialTransactionAggregatorService;
use App\Services\Financial\Filters\FinancialCategoryReportFilter;
use App\Services\Financial\Statistics\FinancialCategoryStatisticsService;

class FinancialCategoryReportService
{
    protected FinancialTransactionAggregatorService $aggregator;
    protected FinancialCategoryStatisticsService $statisticsService;

    public function __construct(
        protected FinancialCategoryReportFilter $filter
    ) {
        $this->aggregator = new FinancialTransactionAggregatorService($filter);
        $this->statisticsService = new FinancialCategoryStatisticsService($filter);
    }

    /**
     * Generate comprehensive financial category report
     */
    public function generateReport(): FinancialCategoryReportDTO
    {
        $statistics = $this->statisticsService->generateStatistics();
        $categorySummaries = $this->getCategorySummaries();

        return new FinancialCategoryReportDTO(
            generatedAt: now()->toIso8601String(),
            filtersApplied: $this->filter->toArray(),
            dateRangeStart: $this->filter->getStartDate(),
            dateRangeEnd: $this->filter->getEndDate(),
            statistics: $statistics,
            categorySummaries: $categorySummaries,
            metadata: [
                'total_categories' => count($categorySummaries),
                'report_type' => 'comprehensive',
            ]
        );
    }

    /**
     * Get category summaries with transaction details
     */
    protected function getCategorySummaries(): array
    {
        $categoryTotals = $this->aggregator->getTotalsByCategory();
        $statusBreakdowns = $this->aggregator->getStatusBreakdownByCategory();
        $branchBreakdowns = $this->aggregator->getBranchBreakdownByCategory();

        $summaries = [];

        foreach ($categoryTotals as $category) {
            $categoryId = $category['category_id'];

            $summaries[] = new CategoryTransactionSummaryDTO(
                categoryId: $categoryId,
                categoryName: $category['category_name'],
                categoryType: $category['category_type'],
                totalAmount: (float) $category['total_amount'],
                transactionCount: (int) $category['transaction_count'],
                averageAmount: (float) $category['average_amount'],
                statusBreakdown: $statusBreakdowns[$categoryId] ?? [],
                branchBreakdown: $branchBreakdowns[$categoryId] ?? [],
                minAmount: (float) $category['min_amount'],
                maxAmount: (float) $category['max_amount'],
            );
        }

        return $summaries;
    }

    /**
     * Generate trend analysis report
     */
    public function generateTrendReport(): array
    {
        $monthlyTrends = $this->aggregator->getMonthlyTrends();

        return [
            'trends' => $monthlyTrends,
            'analysis' => $this->analyzeTrends($monthlyTrends),
        ];
    }

    /**
     * Analyze trends for insights
     */
    protected function analyzeTrends(array $trends): array
    {
        if (empty($trends)) {
            return [
                'trend_direction' => 'neutral',
                'average_monthly_income' => 0,
                'average_monthly_expense' => 0,
                'best_month' => null,
                'worst_month' => null,
            ];
        }

        $totalIncome = 0;
        $totalExpense = 0;
        $bestMonth = null;
        $worstMonth = null;
        $bestBalance = PHP_FLOAT_MIN;
        $worstBalance = PHP_FLOAT_MAX;

        foreach ($trends as $trend) {
            $totalIncome += $trend['income'];
            $totalExpense += $trend['expense'];
            $balance = $trend['income'] - $trend['expense'];

            if ($balance > $bestBalance) {
                $bestBalance = $balance;
                $bestMonth = $trend['month'];
            }

            if ($balance < $worstBalance) {
                $worstBalance = $balance;
                $worstMonth = $trend['month'];
            }
        }

        $monthCount = count($trends);
        $avgIncome = $totalIncome / $monthCount;
        $avgExpense = $totalExpense / $monthCount;

        // Determine trend direction
        $firstHalf = array_slice($trends, 0, ceil($monthCount / 2));
        $secondHalf = array_slice($trends, floor($monthCount / 2));

        $firstHalfAvg = array_sum(array_column($firstHalf, 'income')) - array_sum(array_column($firstHalf, 'expense'));
        $secondHalfAvg = array_sum(array_column($secondHalf, 'income')) - array_sum(array_column($secondHalf, 'expense'));

        $trendDirection = $secondHalfAvg > $firstHalfAvg ? 'improving' : ($secondHalfAvg < $firstHalfAvg ? 'declining' : 'stable');

        return [
            'trend_direction' => $trendDirection,
            'average_monthly_income' => round($avgIncome, 2),
            'average_monthly_expense' => round($avgExpense, 2),
            'best_month' => [
                'month' => $bestMonth,
                'balance' => round($bestBalance, 2),
            ],
            'worst_month' => [
                'month' => $worstMonth,
                'balance' => round($worstBalance, 2),
            ],
        ];
    }

    /**
     * Generate comparison report between two periods
     */
    public function generateComparisonReport(
        FinancialCategoryReportFilter $periodOneFilter,
        FinancialCategoryReportFilter $periodTwoFilter
    ): array {
        $periodOneService = new self($periodOneFilter);
        $periodTwoService = new self($periodTwoFilter);

        $periodOneStats = $periodOneService->statisticsService->getQuickSummary();
        $periodTwoStats = $periodTwoService->statisticsService->getQuickSummary();

        return [
            'period_one' => [
                'date_range' => [
                    'start' => $periodOneFilter->getStartDate(),
                    'end' => $periodOneFilter->getEndDate(),
                ],
                'statistics' => $periodOneStats,
            ],
            'period_two' => [
                'date_range' => [
                    'start' => $periodTwoFilter->getStartDate(),
                    'end' => $periodTwoFilter->getEndDate(),
                ],
                'statistics' => $periodTwoStats,
            ],
            'comparison' => [
                'income_change' => $periodTwoStats['total_income'] - $periodOneStats['total_income'],
                'income_change_percentage' => $this->calculatePercentageChange(
                    $periodOneStats['total_income'],
                    $periodTwoStats['total_income']
                ),
                'expense_change' => $periodTwoStats['total_expense'] - $periodOneStats['total_expense'],
                'expense_change_percentage' => $this->calculatePercentageChange(
                    $periodOneStats['total_expense'],
                    $periodTwoStats['total_expense']
                ),
                'net_balance_change' => $periodTwoStats['net_balance'] - $periodOneStats['net_balance'],
                'transaction_count_change' => $periodTwoStats['total_transactions'] - $periodOneStats['total_transactions'],
            ],
        ];
    }

    /**
     * Calculate percentage change
     */
    protected function calculatePercentageChange(float $oldValue, float $newValue): ?float
    {
        if ($oldValue == 0) {
            return null;
        }

        return round((($newValue - $oldValue) / abs($oldValue)) * 100, 2);
    }

    /**
     * Get detailed report for a specific category
     */
    public function getCategoryDetails(int $categoryId): array
    {
        // Create a new filter with the specific category
        $categoryFilter = new FinancialCategoryReportFilter(
            array_merge(
                $this->filter->toArray(),
                ['category_ids' => [$categoryId]]
            )
        );

        $service = new self($categoryFilter);
        $summaries = $service->getCategorySummaries();

        if (empty($summaries)) {
            return [];
        }

        $summary = $summaries[0];
        $monthlyTrends = $service->aggregator->getMonthlyTrends();

        return [
            'category_summary' => $summary->toArray(),
            'monthly_trends' => $monthlyTrends,
            'statistics' => $service->statisticsService->getQuickSummary(),
        ];
    }
}
