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
    private ?int $storeId = null;
    private ?int $productId = null;
    private ?int $unitId = null;
    private bool $onlyAvailable = false;

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

    public function unit(int $unitId): self
    {
        $this->unitId = $unitId;
        return $this;
    }

    public function onlyAvailable(): self
    {
        $this->onlyAvailable = true;
        return $this;
    }

    /**
     * جلب النتائج مع Pagination
     */
    public function paginate(int $perPage = 50): LengthAwarePaginator
    {
        return $this->buildQuery()->paginate($perPage);
    }

    /**
     * جلب جميع النتائج
     */
    public function get(): Collection
    {
        return $this->buildQuery()
        ->withDetails()
        ->get([
            'product_id',
            'unit_id',
            'package_size',
            'remaining_qty',
        ]);
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

        if ($this->productId) {
            $query->where('product_id', $this->productId);
        }

        if ($this->unitId) {
            $query->where('unit_id', $this->unitId);
        }

        if ($this->onlyAvailable) {
            $query->where('remaining_qty', '>', 0);
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
