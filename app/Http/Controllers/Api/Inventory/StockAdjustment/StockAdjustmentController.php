<?php

namespace App\Http\Controllers\Api\Inventory\StockAdjustment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\StockAdjustment\StockAdjustmentDetailResource;
use App\Services\Inventory\StockAdjustment\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class StockAdjustmentController extends Controller
{
    /**
     * @var StockAdjustmentService
     */
    protected StockAdjustmentService $service;

    /**
     * StockAdjustmentController constructor.
     *
     * @param StockAdjustmentService $service
     */
    public function __construct(StockAdjustmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of stock adjustments (details).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'store_id',
                'product_id',
                'adjustment_type',
                'date_from',
                'date_to',
                'source_id',
                'source_type',
                'sort_by',
                'sort_direction'
            ]);

            $perPage = (int) $request->get('per_page', 15);

            $adjustments = $this->service->getPaginated($filters, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustments retrieved successfully',
                'data' => StockAdjustmentDetailResource::collection($adjustments)->response()->getData(true),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock adjustments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a stock adjustment from inventory detail.
     * Accepts quantity (positive for increase, negative for decrease)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'stock_inventory_detail_id' => 'required|exists:stock_inventory_details,id',
                'product_id' => 'required|exists:products,id',
                'unit_id' => 'required|exists:units,id',
                'quantity' => 'required|numeric', // Can be positive (increase) or negative (decrease)
                'package_size' => 'nullable|numeric|min:0',
                'store_id' => 'nullable|exists:stores,id',
                'reason_id' => 'nullable|exists:stock_adjustment_reasons,id',
                'notes' => 'nullable|string',
            ]);

            $adjustment = $this->service->createFromInventoryDetail(
                $data['stock_inventory_detail_id'],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment created successfully',
                'data' => new StockAdjustmentDetailResource($adjustment),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock adjustment',
                'error' => $e->getMessage(),
            ], (int) $e->getCode() ?: 500);
        }
    }

    /**
     * Display the specified stock adjustment.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $adjustment = $this->service->getPaginated(['id' => $id], 1)->first(); // Simple way to reuse paginated with filter if single find not in service yet or add it

            if (!$adjustment) {
                // Try finding directly
                $adjustment = \App\Models\StockAdjustmentDetail::with(['store', 'product', 'unit', 'createdBy'])->find($id);
            }

            if (!$adjustment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock adjustment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment retrieved successfully',
                'data' => new StockAdjustmentDetailResource($adjustment),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock adjustment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified stock adjustment.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteAdjustment($id);

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment deleted successfully',
            ]);
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'Adjustment not found' ? 404 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
    /**
     * Get list of active stock adjustment reasons.
     *
     * @return JsonResponse
     */
    public function getReasons(): JsonResponse
    {
        try {
            $reasons = \App\Models\StockAdjustmentReason::active()->get(['id', 'name', 'description']);

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment reasons retrieved successfully',
                'data' => $reasons,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock adjustment reasons',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
