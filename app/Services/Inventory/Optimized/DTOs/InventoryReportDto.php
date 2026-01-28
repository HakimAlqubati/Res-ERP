<?php

namespace App\Services\Inventory\Optimized\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * InventoryReportDto
 * 
 * Data Transfer Object لنتيجة تقرير المخزون الكامل
 */
final class InventoryReportDto
{
    /**
     * @param array<int, array<int, ProductInventoryItemDto|array>> $report البيانات (لكل منتج مصفوفة من الوحدات)
     * @param mixed $pagination كائن الـ Pagination أو null
     * @param int|null $totalPages عدد الصفحات الكلي
     */
    public function __construct(
        public readonly array $report,
        public readonly mixed $pagination = null,
        public readonly ?int $totalPages = null,
    ) {}

    /**
     * تحويل إلى مصفوفة للتوافق مع الكود القديم (getInventoryReport)
     */
    public function toLegacyFormat(): array
    {
        return [
            'report' => $this->report,
            'reportData' => $this->report,
            'pagination' => $this->pagination,
        ];
    }

    /**
     * تحويل إلى مصفوفة للتوافق مع (getInventoryReportWithPagination)
     */
    public function toPaginatedFormat(): array
    {
        return [
            'reportData' => $this->report,
            'pagination' => $this->pagination,
            'totalPages' => $this->totalPages ?? ($this->pagination instanceof LengthAwarePaginator
                ? $this->pagination->lastPage()
                : 1),
        ];
    }

    /**
     * الحصول على جميع العناصر كمصفوفة مسطحة
     */
    public function flatten(): array
    {
        $result = [];
        foreach ($this->report as $productItems) {
            foreach ($productItems as $item) {
                $result[] = is_array($item) ? $item : $item->toArray();
            }
        }
        return $result;
    }

    /**
     * فلترة المنتجات حسب شرط معين
     */
    public function filter(callable $callback): array
    {
        $filtered = [];
        foreach ($this->report as $productItems) {
            foreach ($productItems as $item) {
                $itemArray = is_array($item) ? $item : $item->toArray();
                if ($callback($itemArray)) {
                    $filtered[] = $itemArray;
                }
            }
        }
        return $filtered;
    }

    /**
     * الحصول على المنتجات تحت الحد الأدنى
     */
    public function getBelowMinimum(bool $checkLastUnit = true, bool $checkLargestUnit = false): array
    {
        return $this->filter(function ($item) use ($checkLastUnit, $checkLargestUnit) {
            $isTargetUnit = ($checkLastUnit && $item['is_last_unit'])
                || ($checkLargestUnit && $item['is_largest_unit']);

            return $isTargetUnit && $item['remaining_qty'] <= $item['minimum_quantity'];
        });
    }

    /**
     * حساب إجمالي الكمية المتبقية
     */
    public function getTotalRemainingQty(): float
    {
        $total = 0;
        foreach ($this->report as $productItems) {
            foreach ($productItems as $item) {
                $itemArray = is_array($item) ? $item : $item->toArray();
                $total += $itemArray['remaining_qty'] ?? 0;
            }
        }
        return $total;
    }
}
