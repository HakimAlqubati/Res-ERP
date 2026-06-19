<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Responses;

use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchReportResult;
use App\Modules\Stock\Reports\FifoBatchReports\Resources\StockBatchResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

final class StockBatchIndexResponse implements Responsable
{
    /**
     * استقبال مخرجات الاستعلام الخام (سواء كانت Collection أو Paginator)
     */
    public function __construct(
        private readonly StockBatchReportResult $result) {}

    /**
     * تحويل البيانات إلى استجابة HTTP HTTP/JSON Response تلقائياً.
     */
    public function toResponse($request): Response
    {
        // 1. استخراج بيانات الصفحة الحالية لعمليات الجمع
        $currentItems = $this->result->batches instanceof LengthAwarePaginator
            ? collect($this->result->batches->items())
            : $this->result->batches;

        // 2. حساب إجمالي السعر للباتشات المعروضة حالياً (في هذه الصفحة)
        $currentTotalPrice = $currentItems->sum(function ($item) {
            return (float) $item->remaining_total_price;
        });

        return StockBatchResource::collection($this->result->batches)
            ->additional([
                'success' => true,
                'current_summary' => [
                    'total_batches' => $currentItems->count(),
                    'total_price'   => round($currentTotalPrice, 4),
                ],
                'all_summary'     => [
                    'total_batches' => $this->result->totalBatches,
                    'total_price'   => round($this->result->totalPrice, 4),
                ],
            ])
            ->toResponse($request);
    }
}
