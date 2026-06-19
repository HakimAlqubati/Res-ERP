<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Allocator;

use App\Models\Product;
use App\Models\UnitPrice;
use App\Modules\Stock\Reports\FifoBatchReports\Allocator\Helpers\FifoAllocationMapper;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\FifoAllocatorInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class FifoAllocationService implements FifoAllocatorInterface
{ 

    public function __construct(
        private InventoryStockRepositoryInterface $stockRepository,
        private FifoAllocationMapper $mapper
    ) { 
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
        $batches = $this->stockRepository->getAvailableStockBatches(
            new StockBatchFilterDTO(storeId: $storeId, productIds: [$productId])
        );

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

        $batches = $this->stockRepository->getAvailableStockBatches(
            new StockBatchFilterDTO(storeId: $storeId, productIds: [$productId])
        );

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

            $allocations[] = $this->mapper->buildAllocation(
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
