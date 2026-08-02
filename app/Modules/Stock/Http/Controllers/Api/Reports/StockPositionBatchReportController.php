<?php

namespace App\Modules\Stock\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Http\Requests\StockBatchIndexRequest;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\GetAvailableStockBatchesQueryInterface;
use App\Modules\Stock\Reports\FifoBatchReports\Responses\StockBatchIndexResponse;

class StockPositionBatchReportController extends Controller
{
    public function __construct(
        private readonly GetAvailableStockBatchesQueryInterface $stockBatchesQuery,
    ) {}

    /**
     * GET /api/stock/reports/stockPositionBatch
     */
    public function index(StockBatchIndexRequest $request)
    {
        $batches = $this->stockBatchesQuery->execute(
            $request->toDTO()
        );
        
        return new StockBatchIndexResponse($batches);
    }
}
