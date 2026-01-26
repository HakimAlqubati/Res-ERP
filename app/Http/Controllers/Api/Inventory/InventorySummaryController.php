<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\Summary\InventorySummaryFilterDto;
use App\Services\Inventory\Summary\InventorySummaryReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * InventorySummaryController
 * 
 * API Controller for Inventory Summary (v3)
 * يستخدم جدول inventory_summary للسرعة
 */
class InventorySummaryController extends Controller
{
    /**
     * Get inventory summary with flexible filtering
     * 
     * @route GET /api/v3/inventory
     * 
     * @queryParam store_id required int Store ID
     * @queryParam product_id optional int Single product filter
     * @queryParam product_ids optional array Multiple products filter
     * @queryParam unit_id optional int Unit filter
     * @queryParam category_id optional int Category filter
     * @queryParam only_available optional bool Only items with stock > 0
     * @queryParam per_page optional int Items per page (default: 50)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'only_available' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'with_details' => 'nullable|boolean',
        ]);

        $filter = InventorySummaryFilterDto::fromRequest($validated);

        $result = InventorySummaryReportService::make()
            ->filter($filter)
            ->paginate($filter->perPage);

        return response()->json([
            'success' => true,
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'data' => $result->items(),
        ]);
    }
}
