<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

final readonly class StockBatchReportResult
{
    /**
     * @param Collection|LengthAwarePaginator $batches      البيانات (سواء كانت مقسمة أو مجمعة)
     * @param int                             $totalBatches إجمالي عدد الباتشات
     * @param float                           $totalPrice   إجمالي السعر لكل الباتشات
     */
    public function __construct(
        public Collection|LengthAwarePaginator|Paginator $batches,
        public int $totalBatches,
        public float $totalPrice,
    ) {}
}