<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects;

/**
 * فلتر البحث عن باتشات المخزون (FIFO).
 *
 * readonly يمنع تعديل البيانات بعد إنشائها (Immutability).
 */
final readonly class StockBatchFilterDTO
{
    /**
     * @param  int        $storeId         المخزن (مطلوب)
     * @param  int[]      $productIds      معرّفات المنتجات (اختياري – مصفوفة)
     * @param  bool|null  $isCurrentBatch  فلترة الباتش الحالي (null = بدون فلترة)
     * @param  int|null   $perPage         عدد النتائج في الصفحة (null = بدون pagination)
     */
    public function __construct(
        public int $storeId,
        public array $productIds = [],
        public ?bool $isCurrentBatch = null,
        public ?int $perPage = null,
    ) {}

    /**
     * هل نريد تقسيم الصفحات؟
     */
    public function wantsPagination(): bool
    {
        return $this->perPage !== null && $this->perPage > 0;
    }

    /**
     * هل يوجد فلتر على المنتجات؟
     */
    public function hasProductFilter(): bool
    {
        return !empty($this->productIds);
    }
}
