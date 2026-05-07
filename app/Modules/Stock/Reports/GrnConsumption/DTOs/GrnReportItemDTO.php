<?php

namespace App\Modules\Stock\Reports\GrnConsumption\DTOs;

class GrnReportItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $unitName,
        public readonly float $entryQuantity,
        public readonly float $packageSize,
        public readonly ?string $entryDate,
        public readonly float $remainingQuantity,
        public readonly bool $hasStartedLeaving,
        public readonly bool $isCompleted
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'unit_name' => $this->unitName,
            'entry_quantity' => $this->entryQuantity,
            'package_size' => $this->packageSize,
            'entry_date' => $this->entryDate,
            'remaining_quantity' => $this->remainingQuantity,
            'has_started_leaving' => $this->hasStartedLeaving,
            'is_completed' => $this->isCompleted,
        ];
    }
}
