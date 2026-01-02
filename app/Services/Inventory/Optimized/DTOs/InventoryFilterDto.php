<?php

namespace App\Services\Inventory\Optimized\DTOs;

/**
 * InventoryFilterDto
 * 
 * Data Transfer Object للفلاتر المستخدمة في استعلامات المخزون
 * يضمن Type Safety ووضوح المدخلات
 */
final class InventoryFilterDto
{
    public function __construct(
        public readonly ?int $storeId,
        public readonly ?int $categoryId = null,
        public readonly ?int $productId = null,
        public readonly string|int|array|null $unitId = 'all',
        public readonly bool $filterOnlyAvailable = false,
        public readonly bool $isActive = false,
        public readonly array $productIds = [],
    ) {}

    /**
     * إنشاء DTO من المعاملات القديمة للتوافق مع الكود الحالي
     */
    public static function fromLegacyParams(
        ?int $categoryId,
        ?int $productId,
        mixed $unitId,
        ?int $storeId,
        bool $filterOnlyAvailable = false
    ): self {
        return new self(
            storeId: $storeId,
            categoryId: $categoryId,
            productId: $productId,
            unitId: $unitId,
            filterOnlyAvailable: $filterOnlyAvailable,
        );
    }

    /**
     * نسخة معدلة مع productIds
     */
    public function withProductIds(array $productIds): self
    {
        return new self(
            storeId: $this->storeId,
            categoryId: $this->categoryId,
            productId: $this->productId,
            unitId: $this->unitId,
            filterOnlyAvailable: $this->filterOnlyAvailable,
            isActive: $this->isActive,
            productIds: $productIds,
        );
    }

    /**
     * نسخة معدلة مع isActive
     */
    public function withActive(bool $active): self
    {
        return new self(
            storeId: $this->storeId,
            categoryId: $this->categoryId,
            productId: $this->productId,
            unitId: $this->unitId,
            filterOnlyAvailable: $this->filterOnlyAvailable,
            isActive: $active,
            productIds: $this->productIds,
        );
    }

    /**
     * تحديد قائمة المنتجات المطلوبة حسب الفلاتر
     */
    public function getTargetProductIds(): array
    {
        if (!empty($this->productIds)) {
            return $this->productIds;
        }

        if ($this->productId) {
            return [$this->productId];
        }

        return [];
    }

    /**
     * هل الفلتر على وحدة محددة؟
     */
    public function hasUnitFilter(): bool
    {
        return $this->unitId !== 'all' && $this->unitId !== null;
    }

    /**
     * الحصول على unit_id كـ int أو null
     */
    public function getUnitIdAsInt(): ?int
    {
        if (is_numeric($this->unitId)) {
            return (int) $this->unitId;
        }
        return null;
    }
}
