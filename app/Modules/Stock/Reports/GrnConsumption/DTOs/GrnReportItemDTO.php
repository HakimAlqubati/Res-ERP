<?php

namespace App\Modules\Stock\Reports\GrnConsumption\DTOs;

use Carbon\Carbon;

class GrnReportItemDTO
{
    public readonly string $formattedEntryDate;
    public readonly string $statusBadgeClass;
    public readonly string $statusText;
    public readonly string $remainingQuantityColor;

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
    ) {
        $this->formattedEntryDate = $this->entryDate ? Carbon::parse($this->entryDate)->format('M d, Y') : 'N/A';
        $this->remainingQuantityColor = $this->remainingQuantity > 0 ? 'var(--primary)' : 'var(--text-light)';
        
        $status = $this->isCompleted ? \App\Modules\Stock\Reports\Enums\ItemConsumptionStatus::COMPLETED :
                  ($this->hasStartedLeaving ? \App\Modules\Stock\Reports\Enums\ItemConsumptionStatus::CONSUMING : \App\Modules\Stock\Reports\Enums\ItemConsumptionStatus::UNTOUCHED);
        
        $this->statusBadgeClass = $status->badgeClass();
        $this->statusText = $status->label();
    }

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
