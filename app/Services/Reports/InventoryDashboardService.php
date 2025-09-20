<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\GoodsReceivedNote;
use App\Models\Order;
use App\Models\PurchaseInvoice;
use App\Models\StockSupplyOrder;
use Illuminate\Support\Carbon;

class InventoryDashboardService
{
    public function getSummary(): array
    {

        $showGrns          = settingWithDefault('show_dashboard_grns', false);
        $showInvoices      = settingWithDefault('show_dashboard_invoices', false);
        $showBranchOrders  = settingWithDefault('show_dashboard_branch_orders', false);
        $showManufacturing = settingWithDefault('show_dashboard_manufacturing', false);

        $today        = Carbon::today();
        $yesterday    = Carbon::yesterday();
        $startOfMonth = Carbon::now()->startOfMonth();
        // ✅ PROCUREMENT
        $grnsCount = GoodsReceivedNote::approved()
            ->whereDate('created_at', '>=', $startOfMonth)
            ->count();

        $invoicesQuery = PurchaseInvoice::query()
            ->whereBetween('date', [$startOfMonth, $today]);
        $invoicesCount = $invoicesQuery->count();
        $invoicesTotal = $invoicesQuery->with('details')->get()
            ->sum(fn($invoice) => $invoice->total_amount);

        // ✅ BRANCH ORDERS
        $branchOrders = Order::with('branch')
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereDate('created_at', '>=', $startOfMonth)
            ->get()
            ->groupBy(fn($order) => $order->branch?->name ?? 'Unknown Branch')
            ->map(function ($orders, $branchName) {
                return [
                    'branch'        => $branchName,
                    'items'         => $orders->sum(fn($order) => $order->item_count),
                    'month_to_date' => formatMoneyWithCurrency($orders->sum(fn($order) => $order->total_amount)),
                ];
            })->values();

        // ✅ MANUFACTURING (Chocolate)
        $manufacturingOrders = StockSupplyOrder::with('details')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->where('store_id', 9)
            ->whereHas('store.branches', function ($q) {
                $q->where('type', Branch::TYPE_CENTRAL_KITCHEN);
            });
        $itemsMade = (clone $manufacturingOrders)
            ->get()
            ->sum(fn($order) => $order->item_count);

        $todayValue = (clone $manufacturingOrders)
            ->whereDate('created_at', $today)
            ->get()
            ->sum(fn($order) => $order->details->sum(fn($d) => $d->price * $d->quantity));


        $yesterdayValue = (clone $manufacturingOrders)
            ->whereDate('created_at', $yesterday)
            ->get()
            ->sum(fn($order) => $order->details->sum(fn($d) => $d->price * $d->quantity));


        $monthToDateValue = (clone $manufacturingOrders)
            ->get()
            ->sum(fn($order) => $order->details->sum(fn($d) => $d->price * $d->quantity));
        return [
            'procurement'   => array_filter([
                'grns_entered'     => $showGrns ? [
                    'count'         => $grnsCount,
                    'month_to_date' => null,
                ] : null,
                'invoices_entered' => $showInvoices ? [
                    'count'         => $invoicesCount,
                    'month_to_date' => formatMoneyWithCurrency($invoicesTotal),
                ] : null,
            ]),
            'branch_orders' => $showBranchOrders ? $branchOrders->all() : [],

            'manufacturing' => $showManufacturing ? [
                'chocolate' => [
                    'items_made' => $itemsMade,
                    'value'      => [
                        'today'         => formatMoneyWithCurrency($todayValue),
                        'yesterday'     => formatMoneyWithCurrency($yesterdayValue),
                        'month_to_date' => formatMoneyWithCurrency($monthToDateValue),
                    ],
                ],
            ] : [],
        ];
    }
}
