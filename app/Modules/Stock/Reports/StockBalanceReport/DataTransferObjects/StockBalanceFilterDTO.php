<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects;

/**
 * استخدام readonly يمنع تعديل البيانات بعد إنشائها
 * وهذا من أهم مبادئ الـ Clean Code لحماية البيانات (Immutability).
 */
final readonly class StockBalanceFilterDTO
{
    public function __construct(
        public int $storeId,
        public ?int $categoryId = null,
        public array $productIds = [],   // دمجنا (منتج واحد أو عدة منتجات) في مصفوفة واحدة للتبسيط
        public bool $onlyAvailable = false,
        public bool $onlyActive = false,
        public ?int $perPage = null      // إذا كان null، فهذا يعني أننا لا نريد Pagination
    ) {}

    /**
     * دالة مساعدة (Helper) لنعرف لاحقاً هل نحتاج لتقسيم الصفحات أم لا
     */
    public function wantsPagination(): bool
    {
        return $this->perPage !== null && $this->perPage > 0;
    }
}