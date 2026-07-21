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
     * @param  int  $storeId  المخزن (مطلوب)
     * @param  int[]  $productIds  معرّفات المنتجات (اختياري – مصفوفة)
     * @param  bool|null  $isCurrentBatch  فلترة الباتش الحالي (null = بدون فلترة)
     * @param  int|null  $perPage  عدد النتائج في الصفحة (null = بدون pagination)
     */
    public function __construct(
        public int $storeId,
        public array $productIds = [],
        public ?bool $isCurrentBatch = null,
        public ?int $categoryId = null,
        public ?int $perPage = 200,
        public int $page = 1,
        public bool $showNegativeBatches = false ,
        public bool $deductPreviousDeficits = true,
    ) {}

    /**
     * هل نريد تقسيم الصفحات؟
     */
    public function wantsPagination(): bool
    {
        return $this->perPage > 0;
    }

    /**
     * هل يوجد فلتر على المنتجات؟
     */
    public function hasProductFilter(): bool
    {
        return ! empty($this->productIds);
    }

    /**
     * هل يوجد فلتر على التصنيف؟
     */
    public function hasCategoryFilter(): bool
    {
        return $this->categoryId !== null;
    }
}
