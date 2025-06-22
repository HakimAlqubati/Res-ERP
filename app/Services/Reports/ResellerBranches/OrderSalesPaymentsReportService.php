<?php

namespace App\Services\Reports\ResellerBranches;

use App\Models\Branch;
use App\Models\Order;
use Illuminate\Support\Collection;

class OrderSalesPaymentsReportService
{
    /**
     * Generate sales and payments summary by branch.
     *
     * @return Collection
     */
    public function generate(): Collection
    {
        // اجلب جميع الطلبات مع العلاقة مع الفروع
        $orders = Order::with('branch')
            ->whereHas('branch', function ($query) {
                $query->where('type', Branch::TYPE_RESELLER); // غيّر "warehouse" لنوع الفرع الذي تريده
            })
            ->get();

        // نجمع الطلبات حسب branch_id
        $grouped = $orders->groupBy('branch_id');

        return $grouped->map(function ($orders, $branchId) {
            $branchName = $orders->first()?->branch?->name ?? 'Unknown Branch';

            // المبيعات = كل الطلبات
            $totalSales = $orders->sum('total_amount');

            $totalPayments = $orders->sum(function ($order) {
                return $order->paidAmounts->sum('amount');
            });
            $balance = $totalSales - $totalPayments;

            return [
                'branch' => $branchName,
                'sales' => $totalSales,
                'payments' => $totalPayments,
                'balance' => $balance,
            ];
        })->values();
    }
}
