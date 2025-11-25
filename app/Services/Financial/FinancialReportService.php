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
        // 2. Expenses Breakdown (Hierarchical)
        $rawExpenses = $expenseQuery->where('type', 'expense')
            ->with('category.parent')
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->get();

        $groupedExpenses = [];

        foreach ($rawExpenses as $expense) {
            $category = $expense->category;
            if (!$category) continue;

            $amount = (float) $expense->total_amount;

            if ($category->parent_id) {
                // It is a child category
                $parentId = $category->parent_id;

                // Ensure parent exists in our list
                if (!isset($groupedExpenses[$parentId])) {
                    $groupedExpenses[$parentId] = [
                        'category_id' => $parentId,
                        'category_name' => $category->parent->name ?? 'Unknown Parent',
                        'category_description' => $category->parent->description ?? '',
                        'amount' => 0,
                        'children' => [],
                    ];
                }

                // Add to parent's total and children list
                $groupedExpenses[$parentId]['amount'] += $amount;
                $groupedExpenses[$parentId]['children'][] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_description' => $category->description,
                    'amount' => $amount,
                    'amount_formatted' => formatMoneyWithCurrency($amount),
                ];
            } else {
                // It is a root category (or parent itself)
                $id = $category->id;

                if (!isset($groupedExpenses[$id])) {
                    $groupedExpenses[$id] = [
                        'category_id' => $id,
                        'category_name' => $category->name,
                        'category_description' => $category->description,
                        'amount' => 0,
                        'children' => [],
                    ];
                }

                $groupedExpenses[$id]['amount'] += $amount;
            }
        }

        // Format parent amounts
        foreach ($groupedExpenses as &$group) {
            $group['amount_formatted'] = formatMoneyWithCurrency($group['amount']);
        }

        $expenses = collect(array_values($groupedExpenses));

        $totalExpenses = $expenses->sum('amount');

        // 3. Net Profit
        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'revenue' => [
                'total' => (float) $totalRevenue,
                'total_formatted' => formatMoneyWithCurrency($totalRevenue),
                'details' => [],
            ],
            'expenses' => [
                'total' => (float) $totalExpenses,
                'total_formatted' => formatMoneyWithCurrency($totalExpenses),
                'details' => $expenses,
            ],
            'net_profit' => (float) $netProfit,
            'net_profit_formatted' => formatMoneyWithCurrency($netProfit),
        ];
    }
}
