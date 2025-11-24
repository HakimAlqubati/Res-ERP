<?php

namespace App\Services\Financial;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function getIncomeStatement(IncomeStatementRequestDTO $dto): array
    {
        $query = FinancialTransaction::query();

        if ($dto->startDate && $dto->endDate) {
            $query->whereBetween('transaction_date', [$dto->startDate, $dto->endDate]);
        }

        if ($dto->branchId) {
            $query->where('branch_id', $dto->branchId);
        }

        // Clone query for different aggregations
        $revenueQuery = clone $query;
        $expenseQuery = clone $query;

        // 1. Total Revenue
        $totalRevenue = $revenueQuery->where('type', 'income')->sum('amount');

        // 2. Expenses Breakdown
        $expenses = $expenseQuery->where('type', 'expense')
            ->with('category:id,name,description')
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category_name' => $item->category->name ?? 'Uncategorized',
                    'category_description' => $item->category->description ?? '',
                    'amount' => (float) $item->total_amount,
                ];
            });

        $totalExpenses = $expenses->sum('amount');

        // 3. Net Profit
        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'revenue' => [
                'total' => (float) $totalRevenue,
                'details' => [],
            ],
            'expenses' => [
                'total' => (float) $totalExpenses,
                'details' => $expenses,
            ],
            'net_profit' => (float) $netProfit,
        ];
    }
}
