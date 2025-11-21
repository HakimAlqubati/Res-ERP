<?php

namespace App\Services\Financial\Aggregators;

use App\Models\FinancialTransaction;
use App\Services\Financial\Filters\FinancialCategoryReportFilter;
use Illuminate\Support\Facades\DB;

class FinancialTransactionAggregatorService
{
    public function __construct(
        protected FinancialCategoryReportFilter $filter
    ) {}

    /**
     * Get total amounts grouped by category
     */
    public function getTotalsByCategory(): array
    {
        $query = FinancialTransaction::query()
            ->select(
                'category_id',
                'financial_categories.name as category_name',
                'financial_categories.type as category_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(amount) as average_amount'),
                DB::raw('MIN(amount) as min_amount'),
                DB::raw('MAX(amount) as max_amount')
            )
            ->join('financial_categories', 'financial_transactions.category_id', '=', 'financial_categories.id')
            ->groupBy('category_id', 'financial_categories.name', 'financial_categories.type');

        $this->filter->applyToTransactionQuery($query);

        return $query->get()->toArray();
    }

    /**
     * Get status breakdown for each category
     */
    public function getStatusBreakdownByCategory(): array
    {
        $query = FinancialTransaction::query()
            ->select(
                'category_id',
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('category_id', 'status');

        $this->filter->applyToTransactionQuery($query);

        $results = $query->get();

        // Group by category_id
        $breakdown = [];
        foreach ($results as $result) {
            if (!isset($breakdown[$result->category_id])) {
                $breakdown[$result->category_id] = [];
            }
            $breakdown[$result->category_id][$result->status] = [
                'count' => $result->count,
                'total' => round($result->total, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Get branch breakdown for each category
     */
    public function getBranchBreakdownByCategory(): array
    {
        $query = FinancialTransaction::query()
            ->select(
                'category_id',
                'branch_id',
                'branches.name as branch_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->leftJoin('branches', 'financial_transactions.branch_id', '=', 'branches.id')
            ->groupBy('category_id', 'branch_id', 'branches.name');

        $this->filter->applyToTransactionQuery($query);

        $results = $query->get();

        // Group by category_id
        $breakdown = [];
        foreach ($results as $result) {
            if (!isset($breakdown[$result->category_id])) {
                $breakdown[$result->category_id] = [];
            }
            $breakdown[$result->category_id][] = [
                'branch_id' => $result->branch_id,
                'branch_name' => $result->branch_name ?? 'N/A',
                'count' => $result->count,
                'total' => round($result->total, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Get monthly trends
     */
    public function getMonthlyTrends(): array
    {
        $query = FinancialTransaction::query()
            ->select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                'type',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month', 'type')
            ->orderBy('month');

        $this->filter->applyToTransactionQuery($query);

        $results = $query->get();

        // Format results
        $trends = [];
        foreach ($results as $result) {
            if (!isset($trends[$result->month])) {
                $trends[$result->month] = [
                    'month' => $result->month,
                    'income' => 0,
                    'expense' => 0,
                    'income_count' => 0,
                    'expense_count' => 0,
                ];
            }

            if ($result->type === 'income') {
                $trends[$result->month]['income'] = round($result->total, 2);
                $trends[$result->month]['income_count'] = $result->count;
            } else {
                $trends[$result->month]['expense'] = round($result->total, 2);
                $trends[$result->month]['expense_count'] = $result->count;
            }
        }

        return array_values($trends);
    }

    /**
     * Get top categories by amount
     */
    public function getTopCategories(string $type, int $limit = 5): array
    {
        $query = FinancialTransaction::query()
            ->select(
                'category_id',
                'financial_categories.name as category_name',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->join('financial_categories', 'financial_transactions.category_id', '=', 'financial_categories.id')
            ->where('financial_transactions.type', $type)
            ->groupBy('category_id', 'financial_categories.name')
            ->orderByDesc('total_amount')
            ->limit($limit);

        $this->filter->applyToTransactionQuery($query);

        return $query->get()->map(function ($item) {
            return [
                'category_id' => $item->category_id,
                'category_name' => $item->category_name,
                'total_amount' => round($item->total_amount, 2),
                'transaction_count' => $item->transaction_count,
            ];
        })->toArray();
    }

    /**
     * Get status distribution
     */
    public function getStatusDistribution(): array
    {
        $query = FinancialTransaction::query()
            ->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('status');

        $this->filter->applyToTransactionQuery($query);

        return $query->get()->map(function ($item) {
            return [
                'status' => $item->status,
                'count' => $item->count,
                'total' => round($item->total, 2),
            ];
        })->toArray();
    }

    /**
     * Get branch distribution
     */
    public function getBranchDistribution(): array
    {
        $query = FinancialTransaction::query()
            ->select(
                'branch_id',
                'branches.name as branch_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->leftJoin('branches', 'financial_transactions.branch_id', '=', 'branches.id')
            ->groupBy('branch_id', 'branches.name');

        $this->filter->applyToTransactionQuery($query);

        return $query->get()->map(function ($item) {
            return [
                'branch_id' => $item->branch_id,
                'branch_name' => $item->branch_name ?? 'N/A',
                'count' => $item->count,
                'total' => round($item->total, 2),
            ];
        })->toArray();
    }

    /**
     * Get overall totals
     */
    public function getOverallTotals(): array
    {
        $query = FinancialTransaction::query()
            ->select(
                'type',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(amount) as average')
            )
            ->groupBy('type');

        $this->filter->applyToTransactionQuery($query);

        $results = $query->get();

        $totals = [
            'income' => 0,
            'expense' => 0,
            'income_count' => 0,
            'expense_count' => 0,
            'income_average' => 0,
            'expense_average' => 0,
        ];

        foreach ($results as $result) {
            if ($result->type === 'income') {
                $totals['income'] = round($result->total, 2);
                $totals['income_count'] = $result->count;
                $totals['income_average'] = round($result->average, 2);
            } else {
                $totals['expense'] = round($result->total, 2);
                $totals['expense_count'] = $result->count;
                $totals['expense_average'] = round($result->average, 2);
            }
        }

        return $totals;
    }
}
