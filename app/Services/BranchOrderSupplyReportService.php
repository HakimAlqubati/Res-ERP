<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class BranchOrderSupplyReportService
{
    public function branchQuantities($branchId = null)
    {
        $query = InventoryTransaction::query()
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->where('transactionable_type', Order::class)
            ->whereNull('deleted_at')
            ->with(['product', 'unit', 'store']);

        if ($branchId) {
            $query->whereIn('transactionable_id', function ($q) use ($branchId) {
                $q->select('id')->from('orders')->where('branch_id', $branchId);
            });
        }

        $transactions = $query->get();

        $groupedReport = [];

        foreach ($transactions as $transaction) {
            /** @var Order|null $order */
            $order = Order::with('branch')->find($transaction->transactionable_id);
            if (!$order || !$order->branch) {
                continue;
            }

            $key = $order->id . '_' . $transaction->product_id . '_' . $transaction->unit_id;

            if (!isset($groupedReport[$key])) {
                $groupedReport[$key] = [
                    'order_id' => $order->id,
                    'branch_name' => $order->branch->name,
                    'product_name' => $transaction->product?->name,
                    'unit_name' => $transaction->unit?->name,
                    'store_name' => $transaction->store?->name,
                    'supply_date' => $transaction->movement_date,
                    'quantity' => 0,
                    'total_supplied_quantity' => 0,
                ];
            }

            $groupedReport[$key]['quantity'] += $transaction->quantity;
            $groupedReport[$key]['total_supplied_quantity'] += $transaction->quantity * $transaction->package_size;
        }

        return array_values($groupedReport); // Reindex the array numerically
    }
}
