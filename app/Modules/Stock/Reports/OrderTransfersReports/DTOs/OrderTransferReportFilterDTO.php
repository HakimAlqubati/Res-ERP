<?php

namespace App\Modules\Stock\Reports\OrderTransfersReports\DTOs;

use Carbon\Carbon;

class OrderTransferReportFilterDTO
{
    /**
     * نستخدم الـ Constructor Property Promotion مع readonly 
     * لضمان عدم تغيير البيانات بعد إنشائها (Immutable) مما يحسن الأداء.
     */
    public function __construct(
        public readonly ?int $productId,
        public readonly ?string $fromDate,
        public readonly ?string $toDate,
        public readonly array $branchIds,
        public readonly array $categoryIds,
        public readonly ?string $orderNumber,
        public readonly int $page,     // رقم الصفحة الحالية
        public readonly int $perPage
    ) {}

    /**
     * دالة ثابتة (Factory Method) لإنشاء الكائن بسهولة من مصفوفة الفلاتر القادمة من Filament
     */
    public static function fromArray(array $filters,int $page = 1, int $perPage = 200): self
    {
        // 1. معالجة معرف المنتج (ضمان أنه رقم أو Null)
        $productId = null;
        if (!empty($filters['product_id'])) {
            $productId = (int) $filters['product_id'];
        }

        // 2. معالجة التواريخ لتكون نصوصاً صالحة أو Null
        $fromDate = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->toDateTimeString() : null;
        $toDate   = !empty($filters['end_date'])   ? Carbon::parse($filters['end_date'])->endOfDay()->toDateTimeString() : null;

        // 3. ضمان أن الفروع والأصناف مصفوفات دائمًا (تجنب أخطاء array_merge لاحقاً)
        $branchIds   = isset($filters['branch_id']) && is_array($filters['branch_id']) ? $filters['branch_id'] : [];
        $categoryIds = isset($filters['category_id']) && is_array($filters['category_id']) ? $filters['category_id'] : [];

        $orderNumber = !empty($filters['order_number']) ? (string) $filters['order_number'] : null;

        // إرجاع كائن من هذا الكلاس مبني بشكل آمن
        return new self(
            productId: $productId,
            fromDate: $fromDate,
            toDate: $toDate,
            branchIds: $branchIds,
            categoryIds: $categoryIds,
            orderNumber: $orderNumber,
            page: $page,
            perPage: $perPage
        );
    }
}