<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\DTOs;

class FifoBatchReportDTO
{
    public readonly float $totalBaseEntryQty;
    public readonly float $totalBaseConsumedQty;
    public readonly float $totalBaseRemainingQty;
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
        public readonly ?int $baseUnitId,
        public readonly ?string $baseUnitName,
        public readonly array $batches,
    ) {
        $this->totalBaseEntryQty      = round(array_sum(array_column($batches, 'baseEntryQty')), 4);
        $this->totalBaseConsumedQty   = round(array_sum(array_column($batches, 'baseConsumedQty')), 4);
        $this->totalBaseRemainingQty  = round(array_sum(array_column($batches, 'baseRemainingQty')), 4);
        $this->totalInventoryValue    = round(array_sum(array_column($batches, 'totalValue')), 2);
        $this->totalRemainingValue    = round(array_sum(array_column($batches, 'remainingValue')), 2);

        $this->currentBatch = collect($batches)->first(fn(FifoBatchDTO $b) => $b->isCurrentBatch);
    }

    public function currentPrice(): ?float
    {
        return $this->currentBatch?->basePrice;
    }

    public function toArray(): array
    {
        return [
            'product_id'               => $this->productId,
            'product_name'             => $this->productName,
            'product_code'             => $this->productCode,
            'base_unit_id'             => $this->baseUnitId,
            'base_unit_name'           => $this->baseUnitName,
            'total_base_entry_qty'     => $this->totalBaseEntryQty,
            'total_base_consumed_qty'  => $this->totalBaseConsumedQty,
            'total_base_remaining_qty' => $this->totalBaseRemainingQty,
            'total_inventory_value'    => $this->totalInventoryValue,
            'total_remaining_value'    => $this->totalRemainingValue,
            'current_batch'            => $this->currentBatch?->toArray(),
            'current_price'            => $this->currentPrice(),
            'batches'                  => array_map(fn(FifoBatchDTO $b) => $b->toArray(), $this->batches),
        ];
    }
}
