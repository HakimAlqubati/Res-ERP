<?php

namespace App\Traits\Inventory;

use App\Models\StockOutReversal;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

trait HasStockOutReversal
{
    /**
     * Cancel the record and reverse the stock-out transactions.
     *
     * @param string $reason
     * @return StockOutReversal
     * @throws \Exception
     */
    public function cancelAndReverse(string $reason): StockOutReversal
    {
        if (! method_exists($this, 'inventoryTransactions')) {
            throw new \Exception('Model does not have inventoryTransactions relation.');
        }
 

        if (StockOutReversal::where([
            'reversed_type' => get_class($this),
            'reversed_id' => $this->id,
        ])->exists()) {
            throw new \Exception('This record has already been reversed.');
        }

        $transactions = $this->inventoryTransactions()
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->get();

        if ($transactions->isEmpty()) {
            throw new \Exception('No stock-out transactions found to reverse.');
        }

        return DB::transaction(function () use ($transactions, $reason) {
            // Step 1: Mark the record as cancelled
            $this->update([
                'cancelled' => true,
                'cancel_reason' => $reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
            ]);

            // Step 2: Create reversal record
            $reversal = StockOutReversal::create([
                'reversed_type' => get_class($this),
                'reversed_id' => $this->id,
                'store_id' => $this->store_id,
                'reason' => $reason,
                'created_by' => auth()->id(),
            ]);

            // Step 3: Reverse inventory transactions
            foreach ($transactions as $txn) {
                InventoryTransaction::create([
                    'product_id' => $txn->product_id,
                    'unit_id' => $txn->unit_id,
                    'package_size' => $txn->package_size,
                    'price' => $txn->price,
                    'movement_date' => now(),
                    'transaction_date' => now(),
                    'store_id' => $txn->store_id,
                    'quantity' => abs($txn->quantity), // Return the stock
                    'movement_type' => InventoryTransaction::MOVEMENT_IN,
                    'transactionable_id' => $reversal->id,
                    'transactionable_type' => get_class($reversal),
                    'notes' => 'Reversal of stock-out from ' . class_basename($this) . ' #' . $this->id,
                ]);
            }

            return $reversal;
        });
    }
}