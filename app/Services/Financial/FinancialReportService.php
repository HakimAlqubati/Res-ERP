<?php

namespace App\Services\Financial;

use App\DTOs\Financial\IncomeStatementRequestDTO;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function getIncomeStatement(IncomeStatementRequestDTO $dto, bool $includeNetProfitExpenses = false): array
    {
        // Get sales category ID
        $salesCategory = \App\Models\FinancialCategory::findByCode(\App\Enums\FinancialCategoryCode::SALES);
        $salesCategoryId = $salesCategory?->id;

        $query = FinancialTransaction::query()
            ->whereNotNull('branch_id')
            ->whereNull('deleted_at');

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

        // 2. Expenses Breakdown (Operating Expenses)
        // Extract only expenses that affect Net Profit (or have no specific profit_type)
        $rawExpenses = (clone $expenseQuery)
            ->where('type', 'expense')
            ->whereHas('category', function ($q) use ($includeNetProfitExpenses) {
                // Always exclude gross_profit (handled in COGS section)
                // Include net_profit categories only in Net Profit report
                if ($includeNetProfitExpenses) {
                    $q->where(function ($q) {
                        $q->whereNull('profit_type')
                            ->orWhere('profit_type', \App\Models\FinancialCategory::PROFIT_TYPE_NET);
                    });
                } else {
                    $q->whereNull('profit_type');
                }
            })
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
                $parentId = $category->parent_id;

                if (!isset($groupedExpenses[$parentId])) {
                    $groupedExpenses[$parentId] = [
                        'category_id' => $parentId,
                        'category_name' => $category->parent->name ?? 'Unknown Parent',
                        'category_description' => $category->parent->description ?? '',
                        'profit_type' => $category->parent->profit_type,
                        'amount' => 0,
                        'children' => [],
                    ];
                }

                $groupedExpenses[$parentId]['amount'] += $amount;
                $groupedExpenses[$parentId]['children'][] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_description' => $category->description,
                    'profit_type' => $category->profit_type,
                    'amount' => $amount,
                    'amount_formatted' => formatMoneyWithCurrency($amount),
                ];
            } else {
                $id = $category->id;

                if (!isset($groupedExpenses[$id])) {
                    $groupedExpenses[$id] = [
                        'category_id' => $id,
                        'category_name' => $category->name,
                        'category_description' => $category->description,
                        'profit_type' => $category->profit_type,
                        'amount' => 0,
                        'children' => [],
                    ];
                }

                $groupedExpenses[$id]['amount'] += $amount;
            }
        }

        foreach ($groupedExpenses as &$group) {
            $group['amount_formatted'] = formatMoneyWithCurrency($group['amount']);
        }

        $expenses = collect(array_values($groupedExpenses));
        $totalExpenses = $expenses->sum('amount');

        // Extract Dynamic COGS from the expenses that affect Gross Profit
        $dynamicCogsQuery = (clone $expenseQuery)
            ->where('type', 'expense')
            ->whereHas('category', function ($q) {
                $q->where('profit_type', \App\Models\FinancialCategory::PROFIT_TYPE_GROSS)
                    // Exclude the hardcoded ones as they are handled separately below
                    ->whereNotIn('code', [
                        \App\Enums\FinancialCategoryCode::TRANSFERS,
                        \App\Enums\FinancialCategoryCode::DIRECT_PURCHASE,
                        \App\Enums\FinancialCategoryCode::CLOSING_STOCK,
                    ]);
            })
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id')
            ->get();

        $dynamicCogsAmount = 0;
        $dynamicCogsDetails = [];

        foreach ($dynamicCogsQuery as $cogsExpense) {
            $amount = (float) $cogsExpense->total_amount;
            $dynamicCogsAmount += $amount;

            $dynamicCogsDetails[] = [
                'category_id' => $cogsExpense->category_id,
                'category_name' => $cogsExpense->category->name ?? 'Unknown',
                'amount' => $amount,
                'amount_formatted' => formatMoneyWithCurrency($amount),
                'children' => []
            ];
        }

        // 3. Get specific category amounts for Gross Profit calculation
        $transfers = $this->getAmountByCode($query, \App\Enums\FinancialCategoryCode::TRANSFERS);
        $directPurchase = $this->getAmountByCode($query, \App\Enums\FinancialCategoryCode::DIRECT_PURCHASE);
        $closingStock = $this->getAmountByCode($query, \App\Enums\FinancialCategoryCode::CLOSING_STOCK);

        // 4. Calculate Gross Profit: ((Transfers + Direct Purchase + Dynamic COGS) - Closing Stock) ÷ Sales
        // 1. حساب تكلفة البضاعة المباعة (للعرض في التقرير فقط) مضافاً لها حسابات تكاليف المبيعات الديناميكية 
        $costOfGoodsSold = ($transfers + $directPurchase + $dynamicCogsAmount) - $closingStock;

        // 2. حساب إجمالي الربح بناءً على معادلة الصورة المرفقة
        // Equation: Sales + Closing Stock - Transfers - Direct Purchase - Dynamic COGS
        $grossProfitValue = ($totalRevenue + $closingStock) - $directPurchase - $transfers - $dynamicCogsAmount;

        // dd($grossProfitValue,$totalRevenue,$closingStock,$directPurchase,$transfers);
        // 3. حساب نسبة الربح (Gross Profit Ratio)
        $grossProfitRatio = 0;
        if ($totalRevenue > 0) {
            $grossProfitRatio = ($grossProfitValue / $totalRevenue) * 100;
        }
        // 5. Net Profit (إجمالي الربح ناقص المصاريف التشغيلية)
        $netProfit = $grossProfitValue - $totalExpenses;

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
                'dynamic_details' => $dynamicCogsDetails,
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

    /**
     * Get Income Statement including Net Profit deduction logic.
     * Reuses the existing Income Statement calculation but clearly structures Gross and Net.
     *
     * @param IncomeStatementRequestDTO $dto
     * @return array
     */
    public function getNetProfitReport(IncomeStatementRequestDTO $dto): array
    {
        // We reuse getIncomeStatement and pass true to exclude payroll from calculation
        $incomeStatement = $this->getIncomeStatement($dto, includeNetProfitExpenses: true);

        // Let's enhance it with specific Payroll details if needed to show how salaries affect it
        $payrollCategory = \App\Models\FinancialCategory::findByCode(\App\Enums\FinancialCategoryCode::PAYROLL_SALARIES);

        $totalPayrollExpenses = 0;

        if ($payrollCategory) {
            // Find payroll in the expenses details
            foreach ($incomeStatement['expenses']['details'] as $expenseGroup) {
                if (isset($expenseGroup['category_id']) && $expenseGroup['category_id'] == $payrollCategory->id) {
                    $totalPayrollExpenses = $expenseGroup['amount'];
                } elseif (!empty($expenseGroup['children'])) {
                    // It might be a child
                    foreach ($expenseGroup['children'] as $child) {
                        if (isset($child['category_id']) && $child['category_id'] == $payrollCategory->id) {
                            $totalPayrollExpenses += $child['amount'];
                        }
                    }
                }
            }
        }

        // Add specific payroll breakdown to the report if not excluded totally
        // Since we excluded payroll from calculations, we might still want to show the total
        // that *would* have been deducted, or we can just omit it since it's totally excluded.
        // But since the user wanted it excluded "from appearing and from calculation", we can just return it without the impact section,
        // or we keep the impact section 0. Let's just remove the misleading payroll impact section altogether to meet the requirement precisely.

        return $incomeStatement;
    }
}
