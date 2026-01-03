<?php

namespace App\Services\Inventory\Summary;

/**
 * InventorySummaryFilterDto
 * 
 * DTO بسيط للفلترة - يدعم منتج واحد أو متعدد
 */
final class InventorySummaryFilterDto
{
    public function __construct(
        public readonly ?int $storeId = null,
        public readonly array $productIds = [],
        public readonly ?int $unitId = null,
        public readonly ?int $categoryId = null,
        public readonly bool $onlyAvailable = false,
        public readonly int $perPage = 50,
        public readonly bool $withDetails = false,
    ) {}

    /**
     * إنشاء من Request - يدعم product_id (واحد) أو product_ids (متعدد)
     */
    public static function fromRequest(array $data): self
    {
        // دمج product_id و product_ids في مصفوفة واحدة
        $productIds = [];

        if (!empty($data['product_ids'])) {
            $productIds = array_map('intval', (array) $data['product_ids']);
        } elseif (!empty($data['product_id'])) {
            $productIds = [(int) $data['product_id']];
        }

        return new self(
            storeId: isset($data['store_id']) ? (int) $data['store_id'] : null,
            productIds: $productIds,
            unitId: isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            onlyAvailable: (bool) ($data['only_available'] ?? false),
            perPage: (int) ($data['per_page'] ?? 50),
            withDetails: (bool) ($data['with_details'] ?? false),
        );
    }

    /**
     * هل يوجد فلتر منتجات؟
     */
    public function hasProductFilter(): bool
    {
        return !empty($this->productIds);
    }
}
