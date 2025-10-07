<?php

// app/Traits/Inventory/HasStockTransferInventoryTransactions.php

namespace App\Traits\Inventory;

use App\Services\MultiProductsInventoryService;
use App\Services\FifoMethodService;
use App\Models\InventoryTransaction;
use App\Models\StockTransferOrder;
use Exception;
use Illuminate\Support\Facades\Log;

trait HasStockTransferInventoryTransactions
{
    public function createInventoryTransactionsFromTransfer(): void
    {
         foreach ($this->details as $detail) {
            $productName = $detail->product->name . ' ' . $detail->product->code ?? "Unknown Product";
            $storeName = $this->fromStore->name ?? "Unknown Store";
            // Check if quantity is available before proceeding
            $availableQty = MultiProductsInventoryService::getRemainingQty(
                $detail->product_id,
                $detail->unit_id,
                $this->from_store_id
            );

            if ($detail['quantity'] > $availableQty) {
                throw new Exception("Cannot transfer '{$productName}': not enough stock in '{$storeName}'.");
            }

            $fifoService = new FifoMethodService($this);

            $allocations = $fifoService->getAllocateFifo(
                $detail->product_id,
                $detail->unit_id,
                $detail->quantity,
                $this->from_store_id
            );
            if (is_array($allocations) && count($allocations) > 0) {
                $this->processFifoTransferAllocation($allocations, $detail);
            } else {

                throw new Exception("Cannot transfer product '{$productName}': insufficient stock in '{$storeName}'.");
            }
         }
    }

    private function processFifoTransferAllocation($allocations, $detail)
    {
        foreach ($allocations as $alloc) {

            $outTransaction =  InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => InventoryTransaction::MOVEMENT_OUT,
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
            InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => InventoryTransaction::MOVEMENT_IN,
                'quantity'             => $alloc['deducted_qty'],
                'unit_id'              => $alloc['target_unit_id'],
                'package_size'         => $alloc['target_unit_package_size'],
                'price'                => $alloc['price_based_on_unit'],
                'transaction_date'     => $this->date ?? now(),
                'movement_date'        => $this->date ?? now(),
                'store_id'             => $this->to_store_id,
                'notes'                => 'Stock Transfer IN from Store ' . $this->fromStore?->name,
                'transactionable_id'   => $this->id,
                'transactionable_type' => StockTransferOrder::class,
                'source_transaction_id' => $outTransaction->id,
            ]);
        }
    }
}