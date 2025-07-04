<?php

// app/Traits/Inventory/HasStockTransferInventoryTransactions.php

namespace App\Traits\Inventory;

use App\Models\InventoryTransaction;
use App\Models\StockTransferOrder;
use Exception;
use Illuminate\Support\Facades\Log;

trait HasStockTransferInventoryTransactions
{
    public function createInventoryTransactionsFromTransfer(): void
    {
        Log::info('in_trait', []);
        foreach ($this->details as $detail) {
            $productName = $detail->product->name . ' ' . $detail->product->code ?? "Unknown Product";
            $storeName = $this->fromStore->name ?? "Unknown Store";
            // Check if quantity is available before proceeding
            $availableQty = \App\Services\MultiProductsInventoryService::getRemainingQty(
                $detail->product_id,
                $detail->unit_id,
                $this->from_store_id
            );

            if ($detail['quantity'] > $availableQty) {
                throw new \Exception("Cannot transfer '{$productName}': not enough stock in '{$storeName}'.");
            }

            $fifoService = new \App\Services\FifoMethodService($this);

            $allocations = $fifoService->getAllocateFifo(
                $detail->product_id,
                $detail->unit_id,
                $detail->quantity,
                $this->from_store_id
            );
            if (is_array($allocations) && count($allocations) > 0) {
                $this->processFifoTransferAllocation($allocations, $detail);
            } else {

                throw new \Exception("Cannot transfer product '{$productName}': insufficient stock in '{$storeName}'.");
            }
            Log::info('allocation', [$allocations]);
        }
    }

    private function processFifoTransferAllocation($allocations, $detail)
    {
        foreach ($allocations as $alloc) {

            $outTransaction =  \App\Models\InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                'quantity'             => $alloc['deducted_qty'],
                'unit_id'              => $alloc['target_unit_id'],
                'package_size'         => $alloc['target_unit_package_size'],
                'price'                => $alloc['price_based_on_unit'],
                'transaction_date' => $this->date ?? now(),
                'movement_date' => $this->date ?? now(),
                'store_id'             => $alloc['store_id'],
                'notes' => $alloc['notes'],

                'transactionable_id'   => $this->id,
                'transactionable_type' => StockTransferOrder::class,
                'source_transaction_id' => $alloc['transaction_id'],


            ]);

            // 🟩 حركة الإدخال
            \App\Models\InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => \App\Models\InventoryTransaction::MOVEMENT_IN,
                'quantity'             => $alloc['deducted_qty'],
                'unit_id'              => $alloc['target_unit_id'],
                'package_size'         => $alloc['target_unit_package_size'],
                'price'                => $alloc['price_based_on_unit'],
                'transaction_date'     => $this->date ?? now(),
                'movement_date'        => $this->date ?? now(),
                'store_id'             => $this->to_store_id,
                'notes'                => 'Stock Transfer IN from Store ID: ' . $this->from_store_id,
                'transactionable_id'   => $this->id,
                'transactionable_type' => StockTransferOrder::class,
                'source_transaction_id' => $outTransaction->id,
            ]);
        }
    }
}