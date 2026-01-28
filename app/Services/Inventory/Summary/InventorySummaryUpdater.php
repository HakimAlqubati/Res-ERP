<?php

namespace App\Services\Inventory\Summary;

use App\Models\InventorySummary;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

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

        $baseQty = $transaction->quantity * ($transaction->package_size ?? 1);
        $isIn = $transaction->movement_type === InventoryTransaction::MOVEMENT_IN;

        $this->updateAllUnits(
            $transaction->store_id,
            $transaction->product_id,
            $baseQty,
            $isIn
        );
    }

    public function onTransactionUpdated(InventoryTransaction $transaction, array $original): void
    {
        if (!$this->isValid($transaction)) {
            return;
        }

        // تجاهل التحديث إذا لم تتغير القيم المؤثرة على المخزون
        $relevantFields = ['quantity', 'package_size', 'store_id', 'product_id', 'movement_type'];
        $hasRelevantChange = false;

        foreach ($relevantFields as $field) {
            $oldValue = $original[$field] ?? null;
            $newValue = $transaction->{$field} ?? null;
            if ($oldValue != $newValue) {
                $hasRelevantChange = true;
                break;
            }
        }

        if (!$hasRelevantChange) {
            return; // لا تغيير في القيم المؤثرة، لا داعي للتحديث
        }

        // إلغاء القيم القديمة (عكس العملية)
        $oldBaseQty = ($original['quantity'] ?? 0) * ($original['package_size'] ?? 1);
        $oldIsIn = ($original['movement_type'] ?? $transaction->movement_type) === InventoryTransaction::MOVEMENT_IN;

        $this->updateAllUnits(
            $original['store_id'] ?? $transaction->store_id,
            $original['product_id'] ?? $transaction->product_id,
            $oldBaseQty,
            !$oldIsIn // عكس العملية
        );

        // تطبيق القيم الجديدة
        $this->onTransactionCreated($transaction);
    }

    public function onTransactionDeleted(InventoryTransaction $transaction): void
    {
        if (!$this->isValid($transaction)) {
            return;
        }

        $baseQty = $transaction->quantity * ($transaction->package_size ?? 1);
        $isIn = $transaction->movement_type === InventoryTransaction::MOVEMENT_IN;

        // عكس العملية عند الحذف
        $this->updateAllUnits(
            $transaction->store_id,
            $transaction->product_id,
            $baseQty,
            !$isIn
        );
    }

    public function onTransactionRestored(InventoryTransaction $transaction): void
    {
        $this->onTransactionCreated($transaction);
    }

    /**
     * الدالة المركزية لتحديث كل وحدات المنتج
     */
    private function updateAllUnits(int $storeId, int $productId, float $baseQty, bool $isAddition): void
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        $unitPrices = $product->unitPrices()
            ->where('package_size', '>', 0)
            ->orderBy('package_size', 'asc')
            ->get();

        // ✅ تغليف العمليات في transaction لضمان تناسق البيانات
        DB::transaction(function () use ($storeId, $productId, $unitPrices, $baseQty, $isAddition) {
            foreach ($unitPrices as $unitPrice) {
                $summary = InventorySummary::getOrCreate(
                    $storeId,
                    $productId,
                    $unitPrice->unit_id,
                    $unitPrice->package_size
                );

                // تحويل الكمية لهذه الوحدة
                $convertedQty = $baseQty / $unitPrice->package_size;

                if ($isAddition) {
                    $summary->addQty($convertedQty);
                } else {
                    $summary->subtractQty($convertedQty);
                }
            }
        });
    }

    private function isValid(InventoryTransaction $transaction): bool
    {
        return $transaction->store_id && $transaction->product_id && $transaction->unit_id && $transaction->quantity;
    }
}
