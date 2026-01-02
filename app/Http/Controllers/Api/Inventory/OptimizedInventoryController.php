<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\Optimized\DTOs\InventoryFilterDto;
use App\Services\Inventory\Optimized\OptimizedInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OptimizedInventoryController
 * 
 * API Controller for the OptimizedInventoryService
 * Provides endpoints for inventory reporting with optimized performance
 * 
 * ═══════════════════════════════════════════════════════════════════════════════
 * الأداء:
 * - الافتراضي: Pagination (لأفضل أداء)
 * - اختياري: all=true لجلب جميع البيانات (غير مُستحسن للبيانات الكبيرة)
 * ═══════════════════════════════════════════════════════════════════════════════
 */
class OptimizedInventoryController extends Controller
{
    /**
     * Get inventory report (with pagination by default)
     * 
     * @route GET /api/v2/inventory/report
     * 
     * @queryParam store_id required int Store ID
     * @queryParam per_page optional int Items per page (default: 15)
     * @queryParam page optional int Current page number
     * @queryParam all optional bool Set to true to get ALL data (not recommended for large datasets)
     * @queryParam category_id optional int Filter by category
     * @queryParam product_id optional int Filter by single product
     * @queryParam unit_id optional int|string Filter by unit (or 'all')
     * @queryParam only_available optional bool Filter only products with stock > 0
     * @queryParam active optional bool Filter only active products
     * @queryParam product_ids optional array Filter by multiple product IDs
     */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'all' => 'nullable|boolean',
            'category_id' => 'nullable|integer|exists:categories,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'unit_id' => 'nullable',
            'only_available' => 'nullable|boolean',
            'active' => 'nullable|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $filter = new InventoryFilterDto(
            storeId: (int) $validated['store_id'],
            categoryId: $validated['category_id'] ?? null,
            productId: $validated['product_id'] ?? null,
            unitId: $validated['unit_id'] ?? 'all',
            filterOnlyAvailable: (bool) ($validated['only_available'] ?? false),
            isActive: (bool) ($validated['active'] ?? false),
            productIds: $validated['product_ids'] ?? [],
        );

        $service = new OptimizedInventoryService($filter);

        // إذا طُلب جميع البيانات (غير مُستحسن للأداء)
        if ($request->boolean('all')) {
            $report = $service->getInventoryReport();
            return response()->json([
                'success' => true,
                'data' => $report['reportData'] ?? $report['report'] ?? [],
                'pagination' => null,
                'warning' => 'Using all=true is not recommended for large datasets',
            ]);
        }

        // الافتراضي: Pagination (الأفضل للأداء)
        $perPage = (int) ($validated['per_page'] ?? 15);
        $report = $service->getInventoryReportWithPagination($perPage);

        return response()->json([
            'success' => true,
            'data' => $report['reportData'],
            'pagination' => $report['pagination'] ? [
                'current_page' => $report['pagination']->currentPage(),
                'per_page' => $report['pagination']->perPage(),
                'total' => $report['pagination']->total(),
                'last_page' => $report['pagination']->lastPage(),
                'from' => $report['pagination']->firstItem(),
                'to' => $report['pagination']->lastItem(),
            ] : null,
            'total_pages' => $report['totalPages'],
        ]);
    }

    /**
     * Get paginated inventory report (alias for report with pagination)
     * @deprecated Use /report with per_page parameter instead
     */
    public function reportPaginated(Request $request): JsonResponse
    {
        return $this->report($request);
    }

    /**
     * Get inventory for a single product
     * 
     * @route GET /api/v2/inventory/product/{productId}
     * 
     * @urlParam productId required int Product ID
     * @queryParam store_id required int Store ID
     * @queryParam unit_id optional int Filter by unit
     */
    public function productInventory(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'unit_id' => 'nullable|integer',
        ]);

        $filter = new InventoryFilterDto(
            storeId: (int) $validated['store_id'],
            productId: $productId,
            unitId: $validated['unit_id'] ?? 'all',
        );

        $service = new OptimizedInventoryService($filter);
        $inventory = $service->getInventoryForProduct($productId);

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    /**
     * Get remaining quantity for a product/unit combination
     * 
     * @route GET /api/v2/inventory/remaining-qty
     * 
     * @queryParam product_id required int Product ID
     * @queryParam unit_id required int Unit ID
     * @queryParam store_id required int Store ID
     */
    public function remainingQty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'unit_id' => 'required|integer|exists:units,id',
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        $qty = OptimizedInventoryService::getRemainingQty(
            (int) $validated['product_id'],
            (int) $validated['unit_id'],
            (int) $validated['store_id']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => (int) $validated['product_id'],
                'unit_id' => (int) $validated['unit_id'],
                'store_id' => (int) $validated['store_id'],
                'remaining_qty' => $qty,
            ],
        ]);
    }

    /**
     * Get products below minimum quantity
     * 
     * @route GET /api/v2/inventory/low-stock
     * 
     * @queryParam store_id required int Store ID
     * @queryParam category_id optional int Filter by category
     * @queryParam active optional bool Filter only active products
     * @queryParam per_page optional int Items per page (default: 15)
     */
    public function lowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filter = new InventoryFilterDto(
            storeId: (int) $validated['store_id'],
            categoryId: $validated['category_id'] ?? null,
            isActive: (bool) ($validated['active'] ?? false),
        );

        $service = new OptimizedInventoryService($filter);
        $perPage = (int) ($validated['per_page'] ?? 15);

        $lowStock = $service->getProductsBelowMinimumQuantityWithPagination(
            $perPage,
            (bool) ($validated['active'] ?? false)
        );

        return response()->json([
            'success' => true,
            'data' => $lowStock->items(),
            'pagination' => [
                'current_page' => $lowStock->currentPage(),
                'per_page' => $lowStock->perPage(),
                'total' => $lowStock->total(),
                'last_page' => $lowStock->lastPage(),
                'from' => $lowStock->firstItem(),
                'to' => $lowStock->lastItem(),
            ],
        ]);
    }

    /**
     * Get movement report (IN or OUT) for a product
     * 
     * @route GET /api/v2/inventory/movements/{productId}
     * 
     * @urlParam productId required int Product ID
     * @queryParam store_id required int Store ID
     * @queryParam type optional string 'in' or 'out' (default: 'in')
     */
    public function movements(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'type' => 'nullable|in:in,out',
        ]);

        $filter = new InventoryFilterDto(
            storeId: (int) $validated['store_id'],
            productId: $productId,
        );

        $service = new OptimizedInventoryService($filter);
        $type = $validated['type'] ?? 'in';

        $movements = $type === 'in'
            ? $service->getInventoryIn($productId)
            : $service->getInventoryOut($productId);

        return response()->json([
            'success' => true,
            'data' => $movements,
            'movement_type' => $type,
        ]);
    }
}
