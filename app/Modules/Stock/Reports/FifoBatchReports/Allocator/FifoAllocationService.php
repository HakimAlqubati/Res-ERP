<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Allocator;

use App\Models\Product;
use App\Models\UnitPrice;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\FifoAllocatorInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class FifoAllocationService implements FifoAllocatorInterface
{
    private InventoryStockRepositoryInterface $stockRepository;

    public function __construct(?InventoryStockRepositoryInterface $stockRepository = null)
    {
        $this->stockRepository = $stockRepository
            ?? app(InventoryStockRepositoryInterface::class);
    }

    // ─────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────

    /**
     * تخصيص كمية من المخزون حسب FIFO.
     *
     * يعيد استخدام InventoryStockRepository (استعلام SQL واحد)
     * ثم يوزع الكمية المطلوبة على الباتشات بالترتيب.
     *
     * @throws Exception عند عدم كفاية الرصيد أو عدم وجود الوحدة
     */
    public function allocate(
        int $productId,
        int $unitId,
        float $requestedQty,
        int $storeId,
        ?Model $sourceModel = null
    ): array {
        $targetUnit = $this->resolveTargetUnit($productId, $unitId);

        // جلب الباتشات المتاحة من Repository (SQL سريع — استعلام واحد)
        $batches = $this->stockRepository->getAvailableStockBatches($productId, $storeId);

        // التحقق من كفاية الرصيد
        $availableQty = $this->sumAvailableInTargetUnit($batches, $targetUnit);

        if ($requestedQty > $availableQty) {
            $this->throwInsufficientStock($targetUnit, $requestedQty, $availableQty);
        }

        // تخصيص FIFO — المرور على الباتشات بالترتيب
        return $this->walkBatches($batches, $requestedQty, $targetUnit, $unitId, $storeId, $sourceModel);
    }

    /**
     * التحقق من أن الكمية المطلوبة متوفرة في المخزون.
     */
    public function hasEnoughStock(
        int $productId,
        int $unitId,
        float $requestedQty,
        int $storeId
    ): bool {
        return $this->getAvailableQty($productId, $unitId, $storeId) >= $requestedQty;
    }

    /**
     * جلب الرصيد المتاح لمنتج بوحدة معينة في مخزن معين.
     */
    public function getAvailableQty(
        int $productId,
        int $unitId,
        int $storeId
    ): float {
        $targetUnit = $this->resolveTargetUnit($productId, $unitId);

        $batches = $this->stockRepository->getAvailableStockBatches($productId, $storeId);

        return $this->sumAvailableInTargetUnit($batches, $targetUnit);
    }

    // ─────────────────────────────────────────────
    //  Core Allocation Logic
    // ─────────────────────────────────────────────

    /**
     * المرور على الباتشات بترتيب FIFO وتخصيص الكميات.
     */
    private function walkBatches(
        Collection $batches,
        float $requestedQty,
        UnitPrice $targetUnit,
        int $unitId,
        int $storeId,
        ?Model $sourceModel
    ): array {
        $allocations = [];

        foreach ($batches as $batch) {
            if ($requestedQty <= 0) {
                break;
            }

            $remainingInTargetUnit = $this->batchRemainingInTargetUnit($batch, $targetUnit);

            if ($remainingInTargetUnit <= 0) {
                continue;
            }

            $deductQty = min($requestedQty, $remainingInTargetUnit);

            $allocations[] = $this->buildAllocation(
                $batch,
                $deductQty,
                $remainingInTargetUnit,
                $targetUnit,
                $unitId,
                $storeId,
                $sourceModel
            );

            $requestedQty -= $deductQty;
        }

        return $allocations;
    }

    // ─────────────────────────────────────────────
    //  Allocation Builder
    // ─────────────────────────────────────────────

    /**
     * بناء مصفوفة allocation واحدة بنفس الهيكل المتوقع من FifoMethodService.
     *
     * هذا يضمن التوافق مع Order::moveFromInventory() والأماكن الأخرى.
     */
    private function buildAllocation(
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
            'transaction_id'              => $batch->id,
            'store_id'                    => $storeId,
            'unit_id'                     => $batch->unit_id ?? null,

            // الوحدة المطلوبة
            'target_unit_id'              => $unitId,
            'target_unit_package_size'    => $targetUnit->package_size,

            // الأسعار
            'entry_price'                 => (float) $batch->price,
            'price_based_on_unit'         => $price,

            // بيانات الحركة الأصلية
            'package_size'                => (float) $batch->package_size,
            'movement_date'               => $batch->movement_date,
            'transactionable_id'          => $batch->transactionable_id,
            'transactionable_type'        => $batch->transactionable_type,

            // الكميات
            'entry_qty'                   => (float) $batch->in_qty,
            'entry_qty_based_on_unit'     => $entryQtyBasedOnUnit,
            'remaining_qty_based_on_unit' => $remainingInTargetUnit,

            // التخصيص
            'deducted_qty'                       => $deductQty,
            'previous_ordered_qty_based_on_unit' => $previousOutBasedOnUnit,
            'source_order_id'                    => $sourceModel?->id,
            'notes'                              => $notes,
        ];
    }

    // ─────────────────────────────────────────────
    //  Unit Conversion Helpers
    // ─────────────────────────────────────────────

    /**
     * جلب UnitPrice للوحدة المطلوبة.
     *
     * @throws Exception إذا لم يتم العثور على الوحدة
     */
    private function resolveTargetUnit(int $productId, int $unitId): UnitPrice
    {
        $targetUnit = UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->with('unit')
            ->first();

        if (! $targetUnit) {
            throw new Exception(
                "❌ Unit (ID: {$unitId}) not found for product (ID: {$productId})"
            );
        }

        return $targetUnit;
    }

    /**
     * تحويل current_stock (بالوحدة الأساسية/pieces) إلى الوحدة المطلوبة.
     *
     * current_stock من Repository = الرصيد بـ pieces (quantity * package_size)
     * نقسمه على package_size للوحدة المطلوبة للحصول على الكمية بتلك الوحدة.
     */
    private function batchRemainingInTargetUnit(object $batch, UnitPrice $targetUnit): float
    {
        if ($targetUnit->package_size <= 0) {
            return 0.0;
        }

        return round((float) $batch->current_stock / $targetUnit->package_size, 4);
    }

    /**
     * مجموع الرصيد المتاح لكل الباتشات محولاً للوحدة المطلوبة.
     */
    private function sumAvailableInTargetUnit(Collection $batches, UnitPrice $targetUnit): float
    {
        if ($targetUnit->package_size <= 0) {
            return 0.0;
        }

        $totalPieces = $batches->sum('current_stock');

        return round($totalPieces / $targetUnit->package_size, 4);
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
     * بناء نص الملاحظات بشكل احترافي.
     */
    private function buildNotes(
        object $batch,
        float $deductQty,
        float $price,
        UnitPrice $targetUnit,
        ?Model $sourceModel
    ): string {
        $sourceDoc = \Illuminate\Support\Str::headline(class_basename($batch->transactionable_type ?? 'Unknown'));
        $unitName  = $targetUnit->unit->name ?? 'Unit';

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

        $modelName = \Illuminate\Support\Str::headline(class_basename($sourceModel));

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
    //  Error Helpers
    // ─────────────────────────────────────────────

    /**
     * @throws Exception
     */
    private function throwInsufficientStock(
        UnitPrice $targetUnit,
        float $requestedQty,
        float $availableQty
    ): never {
        $productName = $targetUnit->product->name ?? 'Unknown Product';
        $unitName    = $targetUnit->unit->name ?? 'Unknown Unit';

        throw new Exception(
            "❌ Requested quantity ({$requestedQty} {$unitName}) exceeds available inventory ({$availableQty}) for product: {$productName}"
        );
    }
}
