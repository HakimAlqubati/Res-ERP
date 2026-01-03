<?php

namespace App\Services\Inventory\Summary;

use App\Models\InventorySummary;
use App\Models\InventoryTransaction;

/**
 * InventorySummaryUpdater
 * 
 * تحديث جدول inventory_summary عند حركات المخزون
 */
class InventorySummaryUpdater
{
    public function onTransactionCreated(InventoryTransaction $transaction): void
    {
        if (!$this->isValid($transaction)) {
            return;
        }

        $summary = InventorySummary::getOrCreate(
            $transaction->store_id,
            $transaction->product_id,
            $transaction->unit_id,
            $transaction->package_size ?? 1
        );

        $qty = $transaction->quantity * ($transaction->package_size ?? 1);

        if ($transaction->movement_type === InventoryTransaction::MOVEMENT_IN) {
            $summary->addQty($qty);
        } else {
            $summary->subtractQty($qty);
        }
    }

    public function onTransactionUpdated(InventoryTransaction $transaction, array $original): void
    {
        if (!$this->isValid($transaction)) {
            return;
        }

        // إلغاء القيم القديمة
        $oldQty = ($original['quantity'] ?? 0) * ($original['package_size'] ?? 1);
        $oldSummary = InventorySummary::where('store_id', $original['store_id'] ?? $transaction->store_id)
            ->where('product_id', $original['product_id'] ?? $transaction->product_id)
            ->where('unit_id', $original['unit_id'] ?? $transaction->unit_id)
            ->first();

        if ($oldSummary) {
            if (($original['movement_type'] ?? $transaction->movement_type) === InventoryTransaction::MOVEMENT_IN) {
                $oldSummary->subtractQty($oldQty);
            } else {
                $oldSummary->addQty($oldQty);
            }
        }

        // تطبيق القيم الجديدة
        $this->onTransactionCreated($transaction);
    }

    public function onTransactionDeleted(InventoryTransaction $transaction): void
    {
        if (!$this->isValid($transaction)) {
            return;
        }

        $summary = InventorySummary::where('store_id', $transaction->store_id)
            ->where('product_id', $transaction->product_id)
            ->where('unit_id', $transaction->unit_id)
            ->first();

        if (!$summary) {
            return;
        }

        $qty = $transaction->quantity * ($transaction->package_size ?? 1);

        if ($transaction->movement_type === InventoryTransaction::MOVEMENT_IN) {
            $summary->subtractQty($qty);
        } else {
            $summary->addQty($qty);
        }
    }

    public function onTransactionRestored(InventoryTransaction $transaction): void
    {
        $this->onTransactionCreated($transaction);
    }

    private function isValid(InventoryTransaction $transaction): bool
    {
        return $transaction->store_id && $transaction->product_id && $transaction->unit_id && $transaction->quantity;
    }
}
