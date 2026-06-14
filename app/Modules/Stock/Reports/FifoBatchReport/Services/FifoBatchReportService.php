<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Services;

use App\Modules\Stock\Reports\FifoBatchReport\Contracts\FifoBatchRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReport\Contracts\FifoBatchServiceInterface;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchDTO;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchReportDTO;
use Illuminate\Support\Collection;

class FifoBatchReportService implements FifoBatchServiceInterface
{
    public function __construct(
        private readonly FifoBatchRepositoryInterface $repository,
    ) {}

    /**
     * Full report: all batches per product with FIFO layers (base units).
     *
     * @return Collection<int, FifoBatchReportDTO>
     */
    public function getReport(FifoBatchFilterDTO $filter): Collection
    {
        $rows = $this->repository->getBatchesWithConsumption($filter);

        if ($rows->isEmpty()) {
            return collect();
        }

        $reports = $rows
            ->groupBy('product_id')
            ->map(fn(Collection $group) => $this->buildReport($group, $filter->onlyCurrent))
            ->values();

        return $reports;
    }

    /**
     * Get only the current FIFO batch (oldest with remaining qty) for a product.
     */
    public function getCurrentBatch(int $productId, int $unitId, ?int $storeId = null): ?FifoBatchDTO
    {
        $filter = new FifoBatchFilterDTO(productIds: [$productId], unitId: $unitId, storeId: $storeId);

        return $this->getReport($filter)->first()?->currentBatch;
    }

    /**
     * Get the current FIFO price for a product.
     */
    public function getCurrentPrice(int $productId, int $unitId, ?int $storeId = null): ?float
    {
        return $this->getCurrentBatch($productId, $unitId, $storeId)?->basePrice;
    }

    // ─── Internal ────────────────────────────────────────────────────

    private function buildReport(Collection $rows, bool $onlyCurrent = false): FifoBatchReportDTO
    {
        $first        = $rows->first();
        $currentFound = false;

        $batches = $rows->map(function ($row) use (&$currentFound) {
            $isCurrent = false;
            if (!$currentFound && (float) $row->base_remaining_qty > 0) {
                $isCurrent    = true;
                $currentFound = true;
            }

            return new FifoBatchDTO(
                transactionId: $row->id,
                productId: $row->product_id,
                unitId: $row->unit_id,
                unitName: $row->unit_name,
                storeId: $row->store_id,
                entryQty: (float) $row->quantity,
                packageSize: (float) $row->package_size,
                price: (float) $row->price,
                baseEntryQty: (float) $row->base_entry_qty,
                basePrice: (float) $row->base_price,
                movementDate: $row->movement_date,
                sourceType: $row->transactionable_type ? class_basename($row->transactionable_type) : null,
                sourceId: $row->transactionable_id,
                baseConsumedQty: (float) $row->base_consumed_qty,
                isCurrentBatch: $isCurrent,
            );
        });

        if ($onlyCurrent) {
            $batches = $batches->filter(fn(FifoBatchDTO $b) => $b->isCurrentBatch);
        }

        return new FifoBatchReportDTO(
            productId: $first->product_id,
            productName: $first->product_name,
            productCode: $first->product_code,
            baseUnitId: $first->base_unit_id,
            baseUnitName: $first->base_unit_name,
            batches: $batches->values()->all(),
        );
    }
}
