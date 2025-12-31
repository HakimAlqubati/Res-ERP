<?php

namespace App\Services;

use App\Models\Order;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CopyOrderOutToBranchStoreService
{
    public function handle(?int $branchId = null): void
    {
        Order::with(['branch.store'])
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('deleted_at')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereHas('branch.store')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $store = $order->branch?->store;
                    if (! $store) {
                        continue; // لا يوجد مخزن للفرع
                    }
                    DB::transaction(function () use ($store, $order) {

                        InventoryTransaction::where('transactionable_type', Order::class)
                            ->where('transactionable_id', $order->id)
                            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                            ->where('store_id', $store->id)
                            ->withTrashed()
                            ->forceDelete();
                        $outTransactions = InventoryTransaction::where('transactionable_type', Order::class)
                            ->where('transactionable_id', $order->id)
                            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                            ->get(['id', 'product_id', 'quantity', 'unit_id', 'package_size', 'price', 'store_id']);

                        foreach ($outTransactions as $out) {
                            InventoryTransaction::create([
                                'product_id' => $out->product_id,
                                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                                'quantity' => $out->quantity,
                                'unit_id' => $out->unit_id,
                                'movement_date' => $order->transfer_date,
                                'transaction_date' => $order->transfer_date,
                                'package_size' => $out->package_size,
                                'price' => $out->price,
                                'notes' => 'Supplied from Order #' . $order->id,
                                'store_id' => $store->id,
                                'transactionable_type' => Order::class,
                                'transactionable_id' => $order->id,
                                'source_transaction_id' => $out->id,
                            ]);
                        }
                    });
                }
            });
    }

    public function handleForOrder(Order $order): void
    {

        $store = $order->branch?->store;

        if (! $store) {
            return;
        }

        DB::transaction(function () use ($order, $store) {
            InventoryTransaction::where('transactionable_type', Order::class)
                ->where('transactionable_id', $order->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->where('store_id', $store->id)
                ->delete();

            $outTransactions = InventoryTransaction::where('transactionable_type', Order::class)
                ->where('transactionable_id', $order->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->get();

            foreach ($outTransactions as $out) {
                InventoryTransaction::create([
                    'product_id' => $out->product_id,
                    'movement_type' => InventoryTransaction::MOVEMENT_IN,
                    'quantity' => $out->quantity,
                    'unit_id' => $out->unit_id,
                    'movement_date' => $order->transfer_date,
                    'transaction_date' => $order->transfer_date,
                    'package_size' => $out->package_size,
                    'price' => $out->price,
                    'notes' => 'Supplied from Order #' . $order->id,
                    'store_id' => $store->id,
                    'transactionable_type' => Order::class,
                    'transactionable_id' => $order->id,
                    'source_transaction_id' => $out->id,
                ]);
            }
        });
    }
}
