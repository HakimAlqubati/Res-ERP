<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Responses;

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
        private readonly Collection|LengthAwarePaginator|Paginator $batches
    ) {}

    /**
     * تحويل البيانات إلى استجابة HTTP HTTP/JSON Response تلقائياً.
     */
    public function toResponse($request): Response
    {
        return StockBatchResource::collection($this->batches)
            ->additional([
                'success' => true,
            ])
            ->toResponse($request);
    }
}