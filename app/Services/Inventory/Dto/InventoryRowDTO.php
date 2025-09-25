<?php
namespace App\Services\Inventory\Dto;

final class InventoryRowDTO {
    public function __construct(
        public readonly int $productId,
        public readonly string $productCode,
        public readonly string $productName,
        public readonly int $unitId,
        public readonly string $unitName,
        public readonly float $remainingQty,          // في وحدة العرض
        public readonly float $remainingQtyBase,      // في الوحدة الأساس
        public readonly ?float $price,
        public readonly string $priceSource,          // Enum string
        public readonly int $storeId,
        public readonly ?float $minimumQty,
        public readonly bool $belowMinimum,
    ) {}
}