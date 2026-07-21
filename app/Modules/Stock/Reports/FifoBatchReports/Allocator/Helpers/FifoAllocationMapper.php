<?php

namespace App\Modules\Stock\Reports\FifoBatchReports\Allocator\Helpers;

use App\Models\UnitPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FifoAllocationMapper
{
    // ─────────────────────────────────────────────
    //  Allocation Builder
    // ─────────────────────────────────────────────

    /**
     * بناء مصفوفة allocation واحدة بنفس الهيكل المتوقع من FifoMethodService.
     *
     * هذا يضمن التوافق مع Order::moveFromInventory() والأماكن الأخرى.
     */
    public function buildAllocation(
        object $batch,
        float $deductQty,
        float $remainingInTargetUnit,
        UnitPrice $targetUnit,
        int $unitId,
        int $storeId,
        ?Model $sourceModel
    ): array {
        $price = $this->calculatePriceForTargetUnit($batch, $targetUnit);
        $entryQtyBasedOnUnit = $this->entryQtyInTargetUnit($batch, $targetUnit);
        $previousOutBasedOnUnit = $this->previousOutInTargetUnit($batch, $targetUnit);
        $notes = $this->buildNotes($batch, $deductQty, $price, $targetUnit, $sourceModel);

        return [
            // معرفات الحركة
            'transaction_id' => $batch->id,
            'store_id' => $storeId,
            'unit_id' => $batch->unit_id ?? null,

            // الوحدة المطلوبة
            'target_unit_id' => $unitId,
            'target_unit_package_size' => $targetUnit->package_size,

            // الأسعار
            'entry_price' => (float) $batch->price,
            'price_based_on_unit' => $price,

            // بيانات الحركة الأصلية
            'package_size' => (float) $batch->package_size,
            'movement_date' => $batch->movement_date,
            'transactionable_id' => $batch->transactionable_id,
            'transactionable_type' => $batch->transactionable_type,

            // الكميات
            'entry_qty' => (float) $batch->in_qty,
            'entry_qty_based_on_unit' => $entryQtyBasedOnUnit,
            'remaining_qty_based_on_unit' => $remainingInTargetUnit,

            'notes' => $notes,
            // التخصيص
            'deducted_qty' => $deductQty,
            'previous_ordered_qty_based_on_unit' => $previousOutBasedOnUnit,
            'source_order_id' => $sourceModel?->id,
        ];
    }

    /**
     * بناء نص الملاحظات بشكل احترافي.
     */
    private function buildNotes(
        object $batch,
        float $deductQty,
        float $price,
        UnitPrice $targetUnit,
        ?Model $sourceModel
    ): string {
        $sourceDoc = Str::headline(class_basename($batch->transactionable_type ?? 'Unknown'));
        $unitName = $targetUnit->unit->name ?? 'Unit';

        if (! $sourceModel) {
            return sprintf(
                'FIFO allocation: %.4f %s @ %s per unit — sourced from %s #%s',
                $deductQty,
                $unitName,
                number_format($price, 2),
                $sourceDoc,
                $batch->transactionable_id
            );
        }

        $modelName = Str::headline(class_basename($sourceModel));

        return sprintf(
            'FIFO deduction for %s #%s — %.4f %s @ %s per unit — sourced from %s #%s (Batch #%s, dated %s)',
            $modelName,
            $sourceModel->id,
            $deductQty,
            $unitName,
            number_format($price, 2),
            $sourceDoc,
            $batch->transactionable_id,
            $batch->id,
            $batch->movement_date
        );
    }

    // ─────────────────────────────────────────────
    //  Price & Notes Helpers
    // ─────────────────────────────────────────────

    /**
     * حساب السعر محولاً للوحدة المطلوبة.
     *
     * unit_price من Repository = price / package_size (سعر القطعة الواحدة)
     * نضربه في package_size للوحدة المطلوبة.
     */
    private function calculatePriceForTargetUnit(object $batch, UnitPrice $targetUnit): float
    {
        return round((float) $batch->unit_price * $targetUnit->package_size, 4);
    }

    /**
     * كمية الدخول الأصلية محولة للوحدة المطلوبة.
     */
    private function entryQtyInTargetUnit(object $batch, UnitPrice $targetUnit): float
    {
        if ($targetUnit->package_size <= 0) {
            return 0.0;
        }

        return round(((float) $batch->in_qty * (float) $batch->package_size) / $targetUnit->package_size, 4);
    }

    /**
     * الكمية المخصومة سابقاً محولة للوحدة المطلوبة.
     */
    private function previousOutInTargetUnit(object $batch, UnitPrice $targetUnit): float
    {
        if ($targetUnit->package_size <= 0) {
            return 0.0;
        }

        return round((float) $batch->base_unit_out / $targetUnit->package_size, 4);
    }
}
