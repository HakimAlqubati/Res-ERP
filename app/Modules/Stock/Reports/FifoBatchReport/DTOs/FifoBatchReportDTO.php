<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\DTOs;

class FifoBatchReportDTO
{
    public readonly float $totalEntryQty;
    public readonly float $totalConsumedQty;
    public readonly float $totalRemainingQty;
    public readonly float $totalInventoryValue;
    public readonly float $totalRemainingValue;
    public readonly ?FifoBatchDTO $currentBatch;

    /**
     * @param FifoBatchDTO[] $batches
     */
    public function __construct(
        public readonly int $productId,
        public readonly ?string $productName,
        public readonly ?string $productCode,
        public readonly int $unitId,
        public readonly ?string $unitName,
        public readonly float $packageSize,
        public readonly array $batches,
    ) {
        $this->totalEntryQty      = round(array_sum(array_column($batches, 'entryQty')), 4);
        $this->totalConsumedQty   = round(array_sum(array_column($batches, 'consumedQty')), 4);
        $this->totalRemainingQty  = round(array_sum(array_column($batches, 'remainingQty')), 4);
        $this->totalInventoryValue = round(array_sum(array_column($batches, 'totalValue')), 2);
        $this->totalRemainingValue = round(array_sum(array_column($batches, 'remainingValue')), 2);

        $this->currentBatch = collect($batches)->first(fn(FifoBatchDTO $b) => $b->isCurrentBatch);
    }

    public function currentPrice(): ?float
    {
        return $this->currentBatch?->price;
    }

    public function toArray(): array
    {
        return [
            'product_id'           => $this->productId,
            'product_name'         => $this->productName,
            'product_code'         => $this->productCode,
            'unit_id'              => $this->unitId,
            'unit_name'            => $this->unitName,
            'package_size'         => $this->packageSize,
            'total_entry_qty'      => $this->totalEntryQty,
            'total_consumed_qty'   => $this->totalConsumedQty,
            'total_remaining_qty'  => $this->totalRemainingQty,
            'total_inventory_value' => $this->totalInventoryValue,
            'total_remaining_value' => $this->totalRemainingValue,
            'current_batch'        => $this->currentBatch?->toArray(),
            'current_price'        => $this->currentPrice(),
            'batches'              => array_map(fn(FifoBatchDTO $b) => $b->toArray(), $this->batches),
        ];
    }
}
