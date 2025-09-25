<?php

namespace App\Services\Inventory\Dto;

use InvalidArgumentException;

final class InventoryFiltersDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly ?int $categoryId = null,
        public readonly ?array $productIds = null, // nullable = فلتر اختياري 
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly bool $onlyAvailable = false,
        public readonly ?int $perPage = 50,
        public readonly ?int $page = 1,
    ) {}
}
