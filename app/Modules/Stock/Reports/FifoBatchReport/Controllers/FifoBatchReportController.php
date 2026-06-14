<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Reports\FifoBatchReport\Contracts\FifoBatchServiceInterface;
use App\Modules\Stock\Reports\FifoBatchReport\Requests\FifoBatchFilterRequest;
use Illuminate\Http\JsonResponse;

class FifoBatchReportController extends Controller
{
    public function __construct(
        private readonly FifoBatchServiceInterface $service,
    ) {}

    /**
     * GET /api/stock/fifo-batches
     */
    public function index(FifoBatchFilterRequest $request): JsonResponse
    {
        $reports = $this->service->getReport($request->toFilterDTO());

        return response()->json([
            'data'  => $reports->map->toArray()->values(),
            'count' => $reports->count(),
        ]);
    }

    /**
     * GET /api/stock/fifo-batches/current-price?product_id=X&unit_id=Y
     */
    public function currentPrice(FifoBatchFilterRequest $request): JsonResponse
    {
        $filter = $request->toFilterDTO();

        $batch = $this->service->getCurrentBatch(
            $filter->productId,
            $filter->unitId,
            $filter->storeId,
        );

        if (!$batch) {
            return response()->json([
                'current_price' => null,
                'message'       => 'No active batch found.',
            ]);
        }

        return response()->json([
            'current_price' => $batch->price,
            'batch'         => $batch->toArray(),
        ]);
    }
}
