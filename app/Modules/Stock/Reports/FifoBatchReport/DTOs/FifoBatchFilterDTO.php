<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\DTOs;

class FifoBatchFilterDTO
{
    public function __construct(
        public readonly ?array $productIds = null,
        public readonly ?int $unitId = null,
        public readonly ?int $storeId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productIds: self::normalizeIds($data['product_ids'] ?? $data['product_id'] ?? null),
            unitId: $data['unit_id'] ?? null,
            storeId: $data['store_id'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
        );
    }

    /**
     * Accepts: int, "5", [1,2,3], "1,2,3", or null.
     * @return int[]|null
     */
    private static function normalizeIds(mixed $value): ?array
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return array_map('intval', array_filter($value));
        }

        if (is_string($value) && str_contains($value, ',')) {
            return array_map('intval', array_filter(explode(',', $value)));
        }

        return [(int) $value];
    }
}
