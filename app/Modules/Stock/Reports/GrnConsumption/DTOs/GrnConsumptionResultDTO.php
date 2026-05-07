<?php

namespace App\Modules\Stock\Reports\GrnConsumption\DTOs;

class GrnConsumptionResultDTO
{
    public function __construct(
        public readonly int $grnId,
        public readonly string $grnNumber,
        public readonly ?string $grnDate,
        public readonly bool $isLinkedToInvoice,
        public readonly ?string $invoiceNumber,
        /** @var GrnReportItemDTO[] */
        public readonly array $items,
        public readonly bool $isFullyCompleted // هل تم استهلاك كامل السند؟
    ) {}

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
