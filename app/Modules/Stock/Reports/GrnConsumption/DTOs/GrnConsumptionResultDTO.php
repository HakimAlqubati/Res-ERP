<?php

namespace App\Modules\Stock\Reports\GrnConsumption\DTOs;

use Carbon\Carbon;

class GrnConsumptionResultDTO
{
    public readonly string $formattedGrnDate;
    public readonly string $statusBadgeClass;
    public readonly string $statusText;

    public function __construct(
        public readonly int $grnId,
        public readonly string $grnNumber,
        public readonly ?string $grnDate,
        public readonly bool $isLinkedToInvoice,
        public readonly ?string $invoiceNumber,
        /** @var GrnReportItemDTO[] */
        public readonly array $items,
        public readonly bool $isFullyCompleted // هل تم استهلاك كامل السند؟
    ) {
        $this->formattedGrnDate = $this->grnDate ? Carbon::parse($this->grnDate)->format('Y-m-d') : 'No Date';
        
        $status = $this->isFullyCompleted ? \App\Modules\Stock\Reports\Enums\GrnConsumptionStatus::FULLY_COMPLETED : \App\Modules\Stock\Reports\Enums\GrnConsumptionStatus::IN_PROGRESS;
        
        $this->statusBadgeClass = $status->badgeClass();
        $this->statusText = $status->label();
    }

    public function toArray(): array
    {
        return [
            'grn_id' => $this->grnId,
            'grn_number' => $this->grnNumber,
            'grn_date' => $this->grnDate,
            'is_linked_to_invoice' => $this->isLinkedToInvoice,
            'invoice_number' => $this->invoiceNumber,
            'is_fully_completed' => $this->isFullyCompleted,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }
}
