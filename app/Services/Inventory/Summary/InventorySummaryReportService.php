<?php

namespace App\Services\Inventory\Summary;

use App\Models\InventorySummary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * InventorySummaryReportService
 * 
 * تقرير المخزون من جدول summary - بسيط وسريع
 */
class InventorySummaryReportService
{
    private const DEFAULT_PER_PAGE = 15;

    private ?int $storeId = null;
    private ?int $productId = null;
    private array $productIds = [];
    private ?int $unitId = null;
    private ?int $categoryId = null;
    private bool $onlyAvailable = false;
    private bool $includeDetails = false;

    public function store(int $storeId): self
    {
        $this->storeId = $storeId;
        return $this;
    }

    public function product(int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function products(array $productIds): self
    {
        $this->productIds = $productIds;
        return $this;
    }

    public function unit(int $unitId): self
    {
        $this->unitId = $unitId;
        return $this;
    }

    public function category(int $categoryId): self
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function onlyAvailable(): self
    {
        $this->onlyAvailable = true;
        return $this;
    }

    public function withDetails(bool $include = true): self
    {
        $this->includeDetails = $include;
        return $this;
    }

    /**
     * تطبيق فلتر من DTO
     */
    public function filter(InventorySummaryFilterDto $dto): self
    {
        if ($dto->storeId) {
            $this->storeId = $dto->storeId;
        }
        if (!empty($dto->productIds)) {
            $this->productIds = $dto->productIds;
        }
        if ($dto->unitId) {
            $this->unitId = $dto->unitId;
        }
        if ($dto->categoryId) {
            $this->categoryId = $dto->categoryId;
        }
        if ($dto->onlyAvailable) {
            $this->onlyAvailable = true;
        }
        if ($dto->withDetails) {
            $this->includeDetails = true;
        }
        return $this;
    }

    /**
     * جلب النتائج مع Pagination
     */
    public function paginate(int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $result = $this->buildQuery()->paginate($perPage);

        // إخفاء unit_prices و product_items من المنتج
        $result->getCollection()->transform(function ($item) {

            return $item;
        });

        return $result;
    }

    /**
     * جلب جميع النتائج
     */
    public function get(): Collection
    {
        return $this->buildQuery()
            ->get()
        ;
    }

    /**
     * جلب الكمية المتبقية مباشرة
     */
    public function remainingQty(): float
    {
        return $this->buildQuery()->value('remaining_qty') ?? 0.0;
    }

    /**
     * بناء الاستعلام
     */
    private function buildQuery(): Builder
    {
        $query = InventorySummary::query();

        if ($this->storeId) {
            $query->where('store_id', $this->storeId);
        }

        // دعم منتجات متعددة أو منتج واحد
        if (!empty($this->productIds)) {
            $query->whereIn('product_id', $this->productIds);
        } elseif ($this->productId) {
            $query->where('product_id', $this->productId);
        }

        if ($this->unitId) {
            $query->where('unit_id', $this->unitId);
        }

        // فلتر بالتصنيف عبر جدول المنتجات
        if ($this->categoryId) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $this->categoryId));
        }

        if ($this->onlyAvailable) {
            $query->where('remaining_qty', '>', 0);
        }
        $query->select([
            'product_id',
            'unit_id',
            'package_size',
            'remaining_qty',
        ]);

        if ($this->includeDetails) {
            $query->withDetails();
        }
        return $query->orderBy('product_id');
    }

    /**
     * إنشاء instance جديد (للاستخدام المتسلسل)
     */
    public static function make(): self
    {
        return new self();
    }
}
