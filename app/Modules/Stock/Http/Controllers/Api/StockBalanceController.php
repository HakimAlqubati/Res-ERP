<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Http\Requests\StockBalanceIndexRequest;
use App\Modules\Stock\Http\Requests\StockBalanceLowStockRequest;
use App\Modules\Stock\Http\Requests\StockBalanceShowRequest;
use App\Modules\Stock\Reports\StockBalanceReport\Actions\GetLowStockProductsAction;
use App\Modules\Stock\Reports\StockBalanceReport\Actions\GetProductCurrentStockAction;
use App\Modules\Stock\Reports\StockBalanceReport\Actions\GetStockBalanceReportAction;
use Illuminate\Http\JsonResponse;

class StockBalanceController extends Controller
{
    /**
     * GET /api/stock/balances
     */
    public function index(
        StockBalanceIndexRequest $request,
        GetStockBalanceReportAction $action,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data'    => $action->execute($request->toDTO()),
        ]);
    }

    /**
     * GET /api/stock/balances/{productId}
     */
    public function show(
        StockBalanceShowRequest $request,
        GetProductCurrentStockAction $action,
        int $productId,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => true,
                'data'    => $action->execute($productId, $request->storeId()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * GET /api/stock/balances/low-stock
     */
    public function lowStock(
        StockBalanceLowStockRequest $request,
        GetLowStockProductsAction $action,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data'    => $action->execute($request->storeId(), $request->perPage()),
        ]);
    }
}
