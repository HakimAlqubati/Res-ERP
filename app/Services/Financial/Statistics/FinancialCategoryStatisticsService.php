<?php

namespace App\Services\Financial\Statistics;

use App\DTOs\Financial\FinancialCategoryStatisticsDTO;
use App\Services\Financial\Aggregators\FinancialTransactionAggregatorService;
use App\Services\Financial\Filters\FinancialCategoryReportFilter;

class FinancialCategoryStatisticsService
{
    protected FinancialTransactionAggregatorService $aggregator;

    public function __construct(FinancialCategoryReportFilter $filter)
    {
        $this->aggregator = new FinancialTransactionAggregatorService($filter);
    }

    /**
     * Generate comprehensive statistics
     */
    public function generateStatistics(): FinancialCategoryStatisticsDTO
    {
        $totals = $this->aggregator->getOverallTotals();
        $monthlyTrends = $this->aggregator->getMonthlyTrends();
        $topIncomeCategories = $this->aggregator->getTopCategories('income', 5);
        $topExpenseCategories = $this->aggregator->getTopCategories('expense', 5);
        $statusDistribution = $this->aggregator->getStatusDistribution();
        $branchDistribution = $this->aggregator->getBranchDistribution();

        $totalIncome = $totals['income'];
        $totalExpense = $totals['expense'];
        $netBalance = $totalIncome - $totalExpense;

        $totalTransactions = $totals['income_count'] + $totals['expense_count'];
        $averageTransaction = $totalTransactions > 0 
            ? ($totalIncome + $totalExpense) / $totalTransactions 
            : 0;

        $growthRate = $this->calculateGrowthRate($monthlyTrends);

        return new FinancialCategoryStatisticsDTO(
            totalIncome: $totalIncome,
            totalExpense: $totalExpense,
            netBalance: $netBalance,
            totalTransactions: $totalTransactions,
            incomeTransactions: $totals['income_count'],
            expenseTransactions: $totals['expense_count'],
            averageIncome: $totals['income_average'],
            averageExpense: $totals['expense_average'],
            averageTransaction: $averageTransaction,
            monthlyTrends: $monthlyTrends,
            topIncomeCategories: $topIncomeCategories,
            topExpenseCategories: $topExpenseCategories,
            statusDistribution: $statusDistribution,
            branchDistribution: $branchDistribution,
            growthRate: $growthRate,
        );
    }

    /**
     * Calculate growth rate from monthly trends
     */
    protected function calculateGrowthRate(array $monthlyTrends): ?float
    {
        if (count($monthlyTrends) < 2) {
            return null;
        }

        $firstMonth = $monthlyTrends[0];
        $lastMonth = $monthlyTrends[count($monthlyTrends) - 1];

        $firstTotal = $firstMonth['income'] - $firstMonth['expense'];
        $lastTotal = $lastMonth['income'] - $lastMonth['expense'];

        if ($firstTotal == 0) {
            return null;
        }

        return (($lastTotal - $firstTotal) / abs($firstTotal)) * 100;
    }

    /**
     * Get quick summary statistics
     */
    public function getQuickSummary(): array
    {
        $totals = $this->aggregator->getOverallTotals();

        return [
            'total_income' => $totals['income'],
            'total_expense' => $totals['expense'],
            'net_balance' => $totals['income'] - $totals['expense'],
            'total_transactions' => $totals['income_count'] + $totals['expense_count'],
            'income_transactions' => $totals['income_count'],
            'expense_transactions' => $totals['expense_count'],
        ];
    }
}
