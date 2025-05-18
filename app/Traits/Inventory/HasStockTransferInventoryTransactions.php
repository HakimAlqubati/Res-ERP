<?php

// app/Traits/Inventory/HasStockTransferInventoryTransactions.php

namespace App\Traits\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Log;

trait HasStockTransferInventoryTransactions
{
    public function createInventoryTransactionsFromTransfer(): void
    {
        Log::alert('done4', ['hiiiiiiiii', $this->details]);
        foreach ($this->details as $detail) {
            Log::alert('done5', ['hiiiiiiiiizzzzzzzzzzzzzzz']);
            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                'quantity' => $detail->quantity,
                'unit_id' => $detail->unit_id,
                'package_size' => $detail->package_size ?? 1,
                'store_id' => $this->from_store_id,
                'transaction_date' => $this->date ?? now(),
                'movement_date' => $this->date ?? now(),
                'notes' => 'Stock Transfer OUT to Store ID: ' . $this->to_store_id,
                'transactionable_id' => $this->id,
                'transactionable_type' => get_class($this),
            ]);

            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $detail->quantity,
                'unit_id' => $detail->unit_id,
                'package_size' => $detail->package_size ?? 1,
                'store_id' => $this->to_store_id,
                'transaction_date' => $this->date ?? now(),
                'movement_date' => $this->date ?? now(),
                'notes' => 'Stock Transfer IN from Store ID: ' . $this->from_store_id,
                'transactionable_id' => $this->id,
                'transactionable_type' => get_class($this),
            ]);
        }
    }
}
