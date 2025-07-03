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
                Order::READY_FOR_DELEVIRY,
                Order::DELEVIRED,
            ])
            ->get();


        // Build the report data
        $grouped = $orders->groupBy('branch_id');

        return $grouped->map(function ($orders, $branchId) {
            $branch = $orders->first()?->branch;
            $branchName = $branch?->name ?? 'Unknown Branch';

            $doTotal = $orders->sum('total_amount');
            $invoicedTotal = $branch?->total_sales ?? 0;

            $returnedTotal = $orders->sum('total_returned_amount');
            $balance = $doTotal - $invoicedTotal - $returnedTotal;

            return [
                'branch'         => $branchName,
                'do_total'       => $doTotal,
                'invoiced_total' => $invoicedTotal,
                'returned_total' => $returnedTotal,
                'balance'        => $balance,
            ];
        })->values();
    }
}