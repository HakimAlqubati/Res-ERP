<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\DTOs;

class FifoBatchFilterDTO
{
    public function __construct(
        public readonly ?int $productId = null,
        public readonly ?int $unitId = null,
        public readonly ?int $storeId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'] ?? null,
            unitId: $data['unit_id'] ?? null,
            storeId: $data['store_id'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
        );
    }
}
