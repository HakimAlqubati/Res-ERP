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

    public function handleForOrder(Order $order): array
    {
        $order->loadMissing('branch.store');
        $store = $order->branch?->store;

        if (! $store) {
            return [
                'success' => false,
                'message' => __('الفرع غير مرتبط بمخزن صالح أو نشط.'),
                'count'   => 0,
            ];
        }

        return DB::transaction(function () use ($order, $store) {
            $outTransactions = InventoryTransaction::where('transactionable_type', Order::class)
                ->where('transactionable_id', $order->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->get();

            if ($outTransactions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => __('لا توجد حركات صرف (OUT) مسجلة لهذا الطلب لتكرارها كحركات دخول.'),
                    'count'   => 0,
                ];
            }

            // حذف حركات الدخول السابقة لمخزن الفرع لنفس الطلب لتجنب التكرار
            InventoryTransaction::where('transactionable_type', Order::class)
                ->where('transactionable_id', $order->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->where('store_id', $store->id)
                ->delete();

            $count = 0;
            foreach ($outTransactions as $out) {
                InventoryTransaction::create([
                    'product_id'            => $out->product_id,
                    'movement_type'         => InventoryTransaction::MOVEMENT_IN,
                    'quantity'              => $out->quantity,
                    'unit_id'               => $out->unit_id,
                    'package_size'          => $out->package_size,
                    'price'                 => $out->price,
                    'movement_date'         => $out->movement_date ?? $order->transfer_date ?? now(),
                    'transaction_date'      => $out->transaction_date ?? $order->transfer_date ?? now(),
                    'notes'                 => $out->notes ?? ('Supplied from Order #' . $order->id),
                    'store_id'              => $store->id,
                    'transactionable_type'  => Order::class,
                    'transactionable_id'    => $order->id,
                    'source_transaction_id' => $out->id,
                ]);
                $count++;
            }

            return [
                'success' => true,
                'message' => "تم تكرار {$count} حركة دخول (IN) لمخزن الفرع ({$store->name}) بنجاح وبنفس تواريخ الخروج.",
                'count'   => $count,
            ];
        });
    }
}
