<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\DTOs;

class FifoBatchDTO
{
    public readonly float $baseConsumedQty;
    public readonly float $baseRemainingQty;
    public readonly float $totalValue;
    public readonly float $remainingValue;
    public readonly bool $isCurrentBatch;
    public readonly bool $isDepleted;

    public function __construct(
        public readonly int $transactionId,
        public readonly int $productId,
        public readonly int $unitId,
        public readonly string $unitName,
        public readonly int $storeId,
        public readonly float $entryQty,
        public readonly float $packageSize,
        public readonly float $price,
        public readonly float $baseEntryQty,
        public readonly float $basePrice,
        public readonly string $movementDate,
        public readonly ?string $sourceType,
        public readonly ?int $sourceId,
        float $baseConsumedQty,
        bool $isCurrentBatch,
    ) {
        $this->baseConsumedQty = round($baseConsumedQty, 4);
        $this->baseRemainingQty = round($baseEntryQty - $baseConsumedQty, 4);
        
        // Values calculated using base quantities and base price
        $this->totalValue     = round($baseEntryQty * $basePrice, 2);
        $this->remainingValue = round($this->baseRemainingQty * $basePrice, 2);
        
        $this->isCurrentBatch = $isCurrentBatch;
        $this->isDepleted     = $this->baseRemainingQty <= 0;
    }

    public function toArray(): array
    {
        return [
            'transaction_id'     => $this->transactionId,
            'product_id'         => $this->productId,
            'unit_id'            => $this->unitId,
            'unit_name'          => $this->unitName,
            'store_id'           => $this->storeId,
            'entry_qty'          => $this->entryQty,
            'package_size'       => $this->packageSize,
            'price'              => $this->price,
            'base_entry_qty'     => $this->baseEntryQty,
            'base_consumed_qty'  => $this->baseConsumedQty,
            'base_remaining_qty' => $this->baseRemainingQty,
            'base_price'         => $this->basePrice,
            'total_value'        => $this->totalValue,
            'remaining_value'    => $this->remainingValue,
            'movement_date'      => $this->movementDate,
            'source_type'        => $this->sourceType,
            'source_id'          => $this->sourceId,
            'is_current'         => $this->isCurrentBatch,
            'is_depleted'        => $this->isDepleted,
        ];
    }
}
