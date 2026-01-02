<?php

namespace App\Services\Inventory\Optimized\DTOs;

/**
 * InventoryFilterDto
 * 
 * Data Transfer Object للفلاتر المستخدمة في استعلامات المخزون
 * يضمن Type Safety ووضوح المدخلات
 * 
 * ═══════════════════════════════════════════════════════════════════════════════
 * جميع إعدادات الاستعلام موجودة هنا - لا حاجة لتمرير parameters منفصلة
 * ═══════════════════════════════════════════════════════════════════════════════
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
        public readonly int $perPage = 15,
        public readonly bool $includePrices = false,  // تحميل الأسعار (افتراضي: لا)
    ) {}

    /**
     * إنشاء DTO من المعاملات القديمة للتوافق مع الكود الحالي
     */
    public static function fromLegacyParams(
        ?int $categoryId,
        ?int $productId,
        mixed $unitId,
        ?int $storeId,
        bool $filterOnlyAvailable = false,
        int $perPage = 15
    ): self {
        return new self(
            storeId: $storeId,
            categoryId: $categoryId,
            productId: $productId,
            unitId: $unitId,
            filterOnlyAvailable: $filterOnlyAvailable,
            perPage: $perPage,
            includePrices: false,
        );
    }

    /**
     * إنشاء DTO من Request مباشرة
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            categoryId: $validated['category_id'] ?? null,
            productId: $validated['product_id'] ?? null,
            unitId: $validated['unit_id'] ?? 'all',
            filterOnlyAvailable: (bool) ($validated['only_available'] ?? false),
            isActive: (bool) ($validated['active'] ?? false),
            productIds: $validated['product_ids'] ?? [],
            perPage: (int) ($validated['per_page'] ?? 15),
            includePrices: (bool) ($validated['include_prices'] ?? false),
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
            perPage: $this->perPage,
            includePrices: $this->includePrices,
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
            perPage: $this->perPage,
            includePrices: $this->includePrices,
        );
    }

    /**
     * نسخة معدلة مع perPage
     */
    public function withPerPage(int $perPage): self
    {
        return new self(
            storeId: $this->storeId,
            categoryId: $this->categoryId,
            productId: $this->productId,
            unitId: $this->unitId,
            filterOnlyAvailable: $this->filterOnlyAvailable,
            isActive: $this->isActive,
            productIds: $this->productIds,
            perPage: $perPage,
            includePrices: $this->includePrices,
        );
    }

    /**
     * نسخة معدلة مع تضمين الأسعار
     */
    public function withPrices(bool $includePrices = true): self
    {
        return new self(
            storeId: $this->storeId,
            categoryId: $this->categoryId,
            productId: $this->productId,
            unitId: $this->unitId,
            filterOnlyAvailable: $this->filterOnlyAvailable,
            isActive: $this->isActive,
            productIds: $this->productIds,
            perPage: $this->perPage,
            includePrices: $includePrices,
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
