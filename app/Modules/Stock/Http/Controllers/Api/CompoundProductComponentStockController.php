<?php

declare(strict_types=1);

namespace App\Modules\Stock\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Actions\Manufacturing\GetCompoundProductComponentsStockAction;
use App\Modules\Stock\Http\Requests\CompoundProductComponentsStockRequest;
use Illuminate\Http\JsonResponse;

class CompoundProductComponentStockController extends Controller
{
    /**
     * GET /api/stock/manufacturing/compound-products/{compoundProductId}/components-stock
     */
    public function index(
        CompoundProductComponentsStockRequest $request,
        GetCompoundProductComponentsStockAction $action,
        int $compoundProductId,
    ): JsonResponse {
        try {
            $data = $action->execute($compoundProductId, $request->storeId());
            
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
