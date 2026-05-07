<?php

namespace App\Modules\Stock\Reports\ProductGrnAggregation\DTOs;

class ProductAggregationItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $productCode,
        public readonly float $totalEntryQty,
        public readonly float $totalConsumedQty,
        public readonly float $remainingQty,
        public readonly float $consumptionPercentage,
        public readonly bool $isFullyConsumed
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_code' => $this->productCode,
            'total_entry_qty' => $this->totalEntryQty,
            'total_consumed_qty' => $this->totalConsumedQty,
            'remaining_qty' => $this->remainingQty,
            'consumption_percentage' => $this->consumptionPercentage,
            'is_fully_consumed' => $this->isFullyConsumed,
        ];
    }
}
