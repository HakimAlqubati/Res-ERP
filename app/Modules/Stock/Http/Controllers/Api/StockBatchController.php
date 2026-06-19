<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Http\Requests\StockBatchIndexRequest;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;
use Illuminate\Http\JsonResponse;

class StockBatchController extends Controller
{
    public function __construct(
        private readonly InventoryStockRepositoryInterface $stockRepository,
    ) {}

    /**
     * GET /api/stock/stockBatches
     */
    public function index(StockBatchIndexRequest $request): JsonResponse
    {
        $batches = $this->stockRepository->getAvailableStockBatches(
            $request->toDTO()
        );

        return response()->json([
            'success' => true,
            'data'    => $batches,
        ]);
    }
}
