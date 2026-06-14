<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\DTOs;

class FifoBatchDTO
{
    public readonly float $consumedQty;
    public readonly float $remainingQty;
    public readonly float $totalValue;
    public readonly float $remainingValue;
    public readonly bool $isCurrentBatch;
    public readonly bool $isDepleted;

    public function __construct(
        public readonly int $transactionId,
        public readonly int $productId,
        public readonly int $unitId,
        public readonly int $storeId,
        public readonly float $entryQty,
        public readonly float $packageSize,
        public readonly float $price,
        public readonly string $movementDate,
        public readonly ?string $sourceType,
        public readonly ?int $sourceId,
        float $consumedQty,
        bool $isCurrentBatch,
    ) {
        $this->consumedQty    = round($consumedQty, 4);
        $this->remainingQty   = round(max(0, $entryQty - $consumedQty), 4);
        $this->totalValue     = round($entryQty * $price, 2);
        $this->remainingValue = round($this->remainingQty * $price, 2);
        $this->isCurrentBatch = $isCurrentBatch;
        $this->isDepleted     = $this->remainingQty <= 0;
    }

    public function toArray(): array
    {
        return [
            'transaction_id'  => $this->transactionId,
            'product_id'      => $this->productId,
            'unit_id'         => $this->unitId,
            'store_id'        => $this->storeId,
            'entry_qty'       => $this->entryQty,
            'consumed_qty'    => $this->consumedQty,
            'remaining_qty'   => $this->remainingQty,
            'package_size'    => $this->packageSize,
            'price'           => $this->price,
            'total_value'     => $this->totalValue,
            'remaining_value' => $this->remainingValue,
            'movement_date'   => $this->movementDate,
            'source_type'     => $this->sourceType,
            'source_id'       => $this->sourceId,
            'is_current'      => $this->isCurrentBatch,
            'is_depleted'     => $this->isDepleted,
        ];
    }
}
