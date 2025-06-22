<?php

namespace App\Services\Reports\ResellerBranches;

use App\Models\Branch;
use App\Models\Order;
use Illuminate\Support\Collection;

class OrderDeliveryReportService
{
    /**
     * Generate Delivery & Invoicing Report by Branch.
     *
     * @return Collection
     */
    public function generate(): Collection
    {
        // Get all orders that are either ready for delivery or already delivered
        $orders = Order::with('branch')
            ->whereHas('branch', function ($query) {
                $query->where('type', Branch::TYPE_RESELLER); // غيّر "warehouse" لنوع الفرع الذي تريده
            })
            ->whereIn('status', [
                // Order::READY_FOR_DELEVIRY,
                Order::DELEVIRED,
            ])
            ->get();

        // Group orders by branch_id
        $grouped = $orders->groupBy('branch_id');

        // Build the report data
        return $grouped->map(function ($orders, $branchId) {
            $branchName = $orders->first()?->branch?->name ?? 'Unknown Branch';

            $doTotal = $orders
                ->where('status', Order::DELEVIRED)
                ->sum('total_amount');

            $invoicedTotal = $orders->sum(function ($order) {
                return $order->paidAmounts->sum('amount');
            });

            $balance = $doTotal - $invoicedTotal;

            return [
                'branch'          => $branchName,
                'do_total'        => $doTotal,
                'invoiced_total'  => $invoicedTotal,
                'balance'         => $balance,
            ];
        })->values();
    }
}
