<?php

namespace App\DTOs\Financial;

class FinancialCategoryStatisticsDTO
{
    public function __construct(
        public readonly float $totalIncome,
        public readonly float $totalExpense,
        public readonly float $netBalance,
        public readonly int $totalTransactions,
        public readonly int $incomeTransactions,
        public readonly int $expenseTransactions,
        public readonly float $averageIncome,
        public readonly float $averageExpense,
        public readonly float $averageTransaction,
        public readonly array $monthlyTrends,
        public readonly array $topIncomeCategories,
        public readonly array $topExpenseCategories,
        public readonly array $statusDistribution,
        public readonly array $branchDistribution,
        public readonly ?float $growthRate = null,
    ) {}

    public function toArray(): array
    {
        return [
            'totals' => [
                'income' => round($this->totalIncome, 2),
                'expense' => round($this->totalExpense, 2),
                'net_balance' => round($this->netBalance, 2),
            ],
            'transaction_counts' => [
                'total' => $this->totalTransactions,
                'income' => $this->incomeTransactions,
                'expense' => $this->expenseTransactions,
            ],
            'averages' => [
                'income' => round($this->averageIncome, 2),
                'expense' => round($this->averageExpense, 2),
                'overall' => round($this->averageTransaction, 2),
            ],
            'trends' => [
                'monthly' => $this->monthlyTrends,
                'growth_rate' => $this->growthRate ? round($this->growthRate, 2) : null,
            ],
            'top_categories' => [
                'income' => $this->topIncomeCategories,
                'expense' => $this->topExpenseCategories,
            ],
            'distributions' => [
                'status' => $this->statusDistribution,
                'branch' => $this->branchDistribution,
            ],
        ];
    }
}
