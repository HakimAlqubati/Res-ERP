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
        public readonly bool $excludeDepleted = false,
        public readonly bool $onlyCurrent = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productIds: self::normalizeIds($data['product_ids'] ?? null),
            unitId: $data['unit_id'] ?? null,
            storeId: $data['store_id'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            excludeDepleted: filter_var($data['exclude_depleted'] ?? false, FILTER_VALIDATE_BOOLEAN),
            onlyCurrent: filter_var($data['only_current'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
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
