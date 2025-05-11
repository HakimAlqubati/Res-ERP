<?php

namespace App\Services\Analytics;

use App\Models\Branch;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BranchConsumptionAnalysisService
{
    /**
     * Get consumption summary per branch and product
     *
     * @param array $productIds
     * @param array $categoryIds
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */


    public function getBranchConsumption(
        $productIds = [],
        $categoryIds = [],
        $branchIds = [],
        $startDate = null,
        $endDate = null
    ) {
        $startDate = $startDate ?? Carbon::now()->subDays(6)->startOfDay();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $query = DB::table('branches')
            ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('branches.id', '=', 'orders.branch_id')
                    ->whereIn('orders.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
                    ->whereBetween('orders.created_at', [$startDate, $endDate]);
            })
            ->leftJoin('orders_details', 'orders.id', '=', 'orders_details.order_id')
            ->leftJoin('products', 'orders_details.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'branches.id as branch_id',
                'branches.name as branch_name',
                DB::raw("COALESCE(products.name, '-') as product_name"),
                DB::raw("COALESCE(categories.name, '-') as category_name"),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(orders_details.available_quantity) as total_quantity')
            )
            ->where('branches.active', 1)
            ->where('branches.type', Branch::TYPE_BRANCH)
            ->groupBy('branches.id', 'branches.name', 'product_name', 'category_name');

        if (!empty($productIds)) {
            $query->whereIn('products.id', $productIds);
        }

        if (!empty($categoryIds)) {
            $query->whereIn('categories.id', $categoryIds);
        }
        if (!empty($branchIds)) {
            $query->whereIn('branches.id', $branchIds);
        }
        return $query->get();
    }


    /**
     * Compare two periods of consumption
     */
    public function compareTwoPeriods(
        $productIds = [],
        $categoryIds = [],
        $branchIds = [],
        $period1Start,
        $period1End,
        $period2Start,
        $period2End
    ) {
        $first = $this->getBranchConsumption($productIds, $categoryIds, $branchIds, $period1Start, $period1End);
        $second = $this->getBranchConsumption($productIds, $categoryIds, $branchIds, $period2Start, $period2End);

        $keyedFirst = collect($first)->keyBy(function ($item) {
            /** @var object $item */
            return $item->branch_id . '|' . $item->product_name;
        });

        $comparison = collect();

        foreach ($second as $item) {
            $key = $item->branch_id . '|' . $item->product_name;
            $old = $keyedFirst->get($key);

            $previousQty = $old->total_quantity ?? 0;
            $change = $item->total_quantity - $previousQty;
            $percent = $previousQty > 0 ? round(($change / $previousQty) * 100, 1) : null;

            $comparison->push([
                'branch_name' => $item->branch_name,
                'product_name' => $item->product_name,
                'previous_quantity' => $previousQty,
                'current_quantity' => $item->total_quantity,
                'change' => $change,
                'percent_change' => $percent,
            ]);
        }

        return $comparison;
    }
}
