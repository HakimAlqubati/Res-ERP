<?php

namespace App\Services\Financial;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function getIncomeStatement(IncomeStatementRequestDTO $dto): array
    {
        // Get sales category ID
        $salesCategory = \App\Models\FinancialCategory::findByCode(\App\Enums\FinancialCategoryCode::SALES);
        $salesCategoryId = $salesCategory?->id;

        $query = FinancialTransaction::query()
            ->whereNotNull('branch_id');

        if ($dto->startDate && $dto->endDate) {
            $query->whereBetween('transaction_date', [$dto->startDate, $dto->endDate]);
        }

        if ($dto->branchId) {
            $query->where('branch_id', $dto->branchId);
        }

        // Clone query for different aggregations
        $revenueQuery = clone $query;
        $expenseQuery = clone $query;

        // 1. Total Revenue - filter by sales category only
        $totalRevenue = $revenueQuery
            ->where('type', 'income')
            ->when($salesCategoryId, fn($q) => $q->where('category_id', $salesCategoryId))
            ->sum('amount');

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

        // 3. Get specific category amounts for Gross Profit calculation
        $transfers = $this->getAmountByCode($query, \App\Enums\FinancialCategoryCode::TRANSFERS);
        $directPurchase = $this->getAmountByCode($query, \App\Enums\FinancialCategoryCode::DIRECT_PURCHASE);
        $closingStock = $this->getAmountByCode($query, \App\Enums\FinancialCategoryCode::CLOSING_STOCK);

        // 4. Calculate Gross Profit: ((Transfers + Direct Purchase) - Closing Stock) ÷ Sales
        // 1. حساب تكلفة البضاعة المباعة (للعرض في التقرير فقط)
        // COGS = (Transfers + Direct Purchase) - Closing Stock
        $costOfGoodsSold = ($transfers + $directPurchase) - $closingStock;

        // 2. حساب إجمالي الربح بناءً على معادلة الصورة المرفقة
        // Equation: Sales + Closing Stock - Transfers - Direct Purchase
        $grossProfitValue = ($totalRevenue + $closingStock) - $directPurchase - $transfers;

        // dd($grossProfitValue,$totalRevenue,$closingStock,$directPurchase,$transfers);
        // 3. حساب نسبة الربح (Gross Profit Ratio)
        $grossProfitRatio = 0;
        if ($totalRevenue > 0) {
            $grossProfitRatio = ($grossProfitValue / $totalRevenue) * 100;
        }
        // 5. Net Profit
        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'revenue' => [
                'total' => (float) $totalRevenue,
                'total_formatted' => formatMoneyWithCurrency($totalRevenue),
                'details' => [],
            ],
            'cost_of_goods_sold' => [
                'transfers' => (float) $transfers,
                'transfers_formatted' => formatMoneyWithCurrency($transfers),
                'direct_purchase' => (float) $directPurchase,
                'direct_purchase_formatted' => formatMoneyWithCurrency($directPurchase),
                'closing_stock' => (float) $closingStock,
                'closing_stock_formatted' => formatMoneyWithCurrency($closingStock),
                // 'total' => (float) $grossProfitValue,
                // 'total_formatted' => formatMoneyWithCurrency($grossProfitValue),

                'total' => (float) $costOfGoodsSold,
                'total_formatted' => formatMoneyWithCurrency($costOfGoodsSold),

            ],
            'gross_profit' => [
                'value' => (float) $grossProfitValue,
                'value_formatted' => formatMoneyWithCurrency($grossProfitValue),
                'ratio' => (float) $grossProfitRatio,
                'ratio_formatted' => number_format(($grossProfitRatio), 2) . '%',
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

    /**
     * Get total amount for a specific financial category by code.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $categoryCode
     * @return float
     */
    private function getAmountByCode($query, string $categoryCode): float
    {
        $category = \App\Models\FinancialCategory::findByCode($categoryCode);

        if (!$category) {
            return 0.0;
        }

        $clonedQuery = clone $query;

        return (float) $clonedQuery
            ->where('category_id', $category->id)
            ->sum('amount');
    }
}
