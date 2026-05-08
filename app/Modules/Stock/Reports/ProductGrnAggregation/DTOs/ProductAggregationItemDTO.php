<?php

namespace App\Modules\Stock\Reports\ProductGrnAggregation\DTOs;

class ProductAggregationItemDTO
{
    public readonly string $remainingQtyColor;
    public readonly string $progressBarColorClass;
    public readonly string $statusBadgeClass;
    public readonly string $statusText;
    public readonly string $statusBadgeStyle;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $productCode,
        public readonly string $unitName,
        public readonly float $packageSize,
        public readonly float $totalEntryQty,
        public readonly float $totalConsumedQty,
        public readonly float $remainingQty,
        public readonly float $consumptionPercentage,
        public readonly bool $isFullyConsumed
    ) {
        $this->remainingQtyColor = $this->remainingQty > 0 ? 'var(--primary)' : 'var(--text-light)';
        
        if ($this->consumptionPercentage > 85) {
            $this->progressBarColorClass = 'high';
        } elseif ($this->consumptionPercentage > 50) {
            $this->progressBarColorClass = 'med';
        } else {
            $this->progressBarColorClass = 'low';
        }

        $status = $this->isFullyConsumed ? \App\Modules\Stock\Reports\Enums\ItemConsumptionStatus::COMPLETED :
                  ($this->consumptionPercentage > 0 ? \App\Modules\Stock\Reports\Enums\ItemConsumptionStatus::CONSUMING : \App\Modules\Stock\Reports\Enums\ItemConsumptionStatus::UNTOUCHED);
        
        $this->statusBadgeClass = $status->badgeClass();
        $this->statusText = $status->label();
        $this->statusBadgeStyle = ''; // No longer needed as we rely strictly on standardized css classes
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_code' => $this->productCode,
            'unit_name' => $this->unitName,
            'package_size' => $this->packageSize,
            'total_entry_qty' => $this->totalEntryQty,
            'total_consumed_qty' => $this->totalConsumedQty,
            'remaining_qty' => $this->remainingQty,
            'consumption_percentage' => $this->consumptionPercentage,
            'is_fully_consumed' => $this->isFullyConsumed,
        ];
    }
}
