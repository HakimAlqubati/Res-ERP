<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Services;

use App\Modules\Stock\Reports\FifoBatchReport\Contracts\FifoBatchRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchDTO;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchReportDTO;
use Illuminate\Support\Collection;

class FifoBatchReportService
{
    public function __construct(
        private readonly FifoBatchRepositoryInterface $repository,
    ) {}

    /**
     * Full report: all batches per product+unit with FIFO layers.
     *
     * @return Collection<int, FifoBatchReportDTO>
     */
    public function getReport(FifoBatchFilterDTO $filter): Collection
    {
        $rows = $this->repository->getBatchesWithConsumption($filter);

        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->groupBy(fn($row) => $row->product_id . '_' . $row->unit_id)
            ->map(fn(Collection $group) => $this->buildReport($group))
            ->values();
    }

    /**
     * Get only the current FIFO batch (oldest with remaining qty) for a product+unit.
     */
    public function getCurrentBatch(int $productId, int $unitId, ?int $storeId = null): ?FifoBatchDTO
    {
        $filter = new FifoBatchFilterDTO(productId: $productId, unitId: $unitId, storeId: $storeId);

        return $this->getReport($filter)->first()?->currentBatch;
    }

    /**
     * Get the current FIFO price for a product+unit.
     */
    public function getCurrentPrice(int $productId, int $unitId, ?int $storeId = null): ?float
    {
        return $this->getCurrentBatch($productId, $unitId, $storeId)?->price;
    }

    // ─── Internal ────────────────────────────────────────────────────

    private function buildReport(Collection $rows): FifoBatchReportDTO
    {
        $first        = $rows->first();
        $currentFound = false;

        $batches = $rows->map(function ($row) use (&$currentFound) {
            $isCurrent = false;
            if (!$currentFound && (float) $row->remaining_qty > 0) {
                $isCurrent    = true;
                $currentFound = true;
            }

            return new FifoBatchDTO(
                transactionId: $row->id,
                productId: $row->product_id,
                unitId: $row->unit_id,
                storeId: $row->store_id,
                entryQty: (float) $row->quantity,
                packageSize: (float) $row->package_size,
                price: (float) $row->price,
                movementDate: $row->movement_date,
                sourceType: $row->transactionable_type ? class_basename($row->transactionable_type) : null,
                sourceId: $row->transactionable_id,
                consumedQty: (float) $row->consumed_qty,
                isCurrentBatch: $isCurrent,
            );
        })->all();

        return new FifoBatchReportDTO(
            productId: $first->product_id,
            productName: $first->product_name,
            productCode: $first->product_code,
            unitId: $first->unit_id,
            unitName: $first->unit_name,
            packageSize: (float) $first->package_size,
            batches: $batches,
        );
    }
}
