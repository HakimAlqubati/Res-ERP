<?php

namespace App\Services\FixFifo;

use App\Models\InventoryTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FifoAllocationSaver
{
    public static function save(array $allocations, int $productId)
    {
        DB::transaction(function () use ($allocations, $productId) {
            if ($productId == 0) {
                InventoryTransaction::where('transactionable_type', Order::class)
                    ->forceDelete();
            } else {
                InventoryTransaction::where('transactionable_type', Order::class)
                    ->where('product_id', $productId)
                    ->forceDelete();
            }
            foreach ($allocations as $allocation) {
                InventoryTransaction::create([
                    'product_id'            => $allocation['product_id'],
                    'movement_type'         => InventoryTransaction::MOVEMENT_OUT,
                    'quantity'               => $allocation['quantity'],
                    'unit_id'               => $allocation['unit_id'],
                    'package_size'          => $allocation['package_size'],
                    'store_id'              => $allocation['store_id'],
                    'price'                 => $allocation['price'],
                    'transaction_date'      => $allocation['created_at'],
                    'movement_date'      => $allocation['created_at'],
                    'notes'                 => $allocation['notes'],
                    'transactionable_id'    => $allocation['order_id'],
                    'transactionable_type' => $allocation['transactionable_type'],
                    'source_transaction_id' => $allocation['source_transaction_id'],
                ]);
            }
        });
    }
}
