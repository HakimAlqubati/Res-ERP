<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Http\Requests\StockBatchIndexRequest;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Responses\StockBatchIndexResponse;
use Illuminate\Http\JsonResponse;

class StockBatchController extends Controller
{
    public function __construct(
        private readonly GetAvailableStockBatchesQueryInterface $stockBatchesQuery,
    ) {}

    /**
     * GET /api/stock/stockBatches
     */
    public function index(StockBatchIndexRequest $request)
    {
        $batches = $this->stockBatchesQuery->execute(
            $request->toDTO()
        );
        
        return new StockBatchIndexResponse($batches);

       
    }
}
