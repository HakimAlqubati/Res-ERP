<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;
use App\Modules\Stock\Reports\FifoBatchReport\Services\FifoBatchReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FifoBatchReportController extends Controller
{
    public function __construct(
        private readonly FifoBatchReportService $service,
    ) {}

    /**
     * GET /api/stock/fifo-batches
     * Full FIFO batch layers report.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'unit_id'    => 'nullable|integer|exists:units,id',
            'store_id'   => 'nullable|integer|exists:stores,id',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);

        $filter = FifoBatchFilterDTO::fromArray($request->all());
        $reports = $this->service->getReport($filter);

        return response()->json([
            'data'  => $reports->map->toArray()->values(),
            'count' => $reports->count(),
        ]);
    }

    /**
     * GET /api/stock/fifo-batches/current-price?product_id=X&unit_id=Y
     * Quick lookup: current FIFO price for a product+unit.
     */
    public function currentPrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'unit_id'    => 'required|integer|exists:units,id',
            'store_id'   => 'nullable|integer|exists:stores,id',
        ]);

        $batch = $this->service->getCurrentBatch(
            $request->integer('product_id'),
            $request->integer('unit_id'),
            $request->integer('store_id') ?: null,
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
