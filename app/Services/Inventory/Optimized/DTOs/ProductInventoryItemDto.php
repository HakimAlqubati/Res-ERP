<?php

namespace App\Services\Inventory\Optimized\DTOs;

/**
 * ProductInventoryItemDto
 * 
 * Data Transfer Object لعنصر مخزون منتج واحد (وحدة واحدة)
 * يمثل سطر واحد في تقرير المخزون
 */
final class ProductInventoryItemDto
{
    public function __construct(
        public readonly int $productId,
        public readonly bool $productActive,
        public readonly ?string $productCode,
        public readonly string $productName,
        public readonly int $unitId,
        public readonly int $order,
        public readonly float $packageSize,
        public readonly string $unitName,
        public readonly float $remainingQty,
        public readonly float $remainingQuantityBase,
        public readonly ?int $baseUnitId,
        public readonly ?string $baseUnitName,
        public readonly float $minimumQuantity,
        public readonly bool $isLastUnit,
        public readonly bool $isLargestUnit,
        public readonly ?float $price,
        public readonly string $priceSource,
        public readonly ?int $priceStoreId,
    ) {}

    /**
     * تحويل إلى مصفوفة للتوافق مع الكود القديم
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_active' => $this->productActive,
            'product_code' => $this->productCode,
            'product_name' => $this->productName,
            'unit_id' => $this->unitId,
            'order' => $this->order,
            'package_size' => $this->packageSize,
            'unit_name' => $this->unitName,
            'remaining_qty' => $this->remainingQty,
            'remaining_quantity_base' => $this->remainingQuantityBase,
            'base_unit_id' => $this->baseUnitId,
            'base_unit_name' => $this->baseUnitName,
            'minimum_quantity' => $this->minimumQuantity,
            'is_last_unit' => $this->isLastUnit,
            'is_largest_unit' => $this->isLargestUnit,
            'price' => $this->price,
            'price_source' => $this->priceSource,
            'price_store_id' => $this->priceStoreId,
        ];
    }

    /**
     * هل المنتج تحت الحد الأدنى؟
     */
    public function isBelowMinimum(): bool
    {
        return $this->remainingQty <= $this->minimumQuantity;
    }
}
