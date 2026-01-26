<?php

namespace App\Http\Controllers\Api\Developer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Developer Benchmark Controller
 * للمقارنة بين Services المخزون
 * ⚠️ للتطوير فقط
 */
class DevBenchmarkController extends Controller
{
    /**
     * Benchmark للمقارنة بين Service القديم والجديد
     * 
     * POST /api/dev/benchmark/summary
     * Body: { "store_id": 1, "count": 100, "use_new": true }
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'count' => 'nullable|integer|min:1|max:1000',
            'use_new' => 'nullable|boolean',
        ]);

        $storeId = (int) $request->store_id;
        $count = $request->input('count', 100);
        $useNew = $request->input('use_new', true);

        $startTime = microtime(true);

        $products = Product::where('active', 1)
            ->where('type', '!=', Product::TYPE_FINISHED_POS)
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();

        $productsCount = count($products);
        $results = [];
        $serviceName = $useNew ? 'InventorySummaryReportService (NEW)' : 'MultiProductsInventoryService (OLD)';

        foreach ($products as $productId) {
            if ($useNew) {
                $data = \App\Services\Inventory\Summary\InventorySummaryReportService::make()
                    ->store($storeId)
                    ->product($productId)
                    ->withDetails()
                    ->get();

                $results[] = [
                    'product_id' => $productId,
                    'units_count' => $data->count(),
                    'total_qty' => $data->sum('remaining_qty'),
                ];
            } else {
                $service = new \App\Services\MultiProductsInventoryService(
                    null,
                    $productId,
                    'all',
                    $storeId,
                    false
                );
                $data = $service->getInventoryForProduct($productId);

                $results[] = [
                    'product_id' => $productId,
                    'units_count' => count($data),
                    'total_qty' => array_sum(array_column($data, 'remaining_qty')),
                ];
            }
        }

        $endTime = microtime(true);
        $totalTimeMs = round(($endTime - $startTime) * 1000, 2);
        $avgTimePerProduct = $productsCount > 0 ? round($totalTimeMs / $productsCount, 2) : 0;

        return response()->json([
            'success' => true,
            'service' => $serviceName,
            'benchmark' => [
                'products_processed' => $productsCount,
                'total_time_ms' => $totalTimeMs,
                'avg_time_per_product_ms' => $avgTimePerProduct,
                'store_id' => $storeId,
            ],
            'sample_results' => array_slice($results, 0, 5),
        ]);
    }

    /**
     * مقارنة ذكية: نفس المنتجات لكلا الـ Services في طلب واحد
     * 
     * POST /api/dev/benchmark/compare
     * Body: { "store_id": 1, "count": 100 }
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'count' => 'nullable|integer|min:1|max:500',
        ]);

        $storeId = (int) $request->store_id;
        $count = $request->input('count', 100);

        $products = Product::where('active', 1)
            ->where('type', '!=', Product::TYPE_FINISHED_POS)
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();

        $productsCount = count($products);

        // الـ Service الجديد
        $startNew = microtime(true);
        $newResults = [];

        foreach ($products as $productId) {
            $data = \App\Services\Inventory\Summary\InventorySummaryReportService::make()
                ->store($storeId)
                ->product($productId)
                ->withDetails()
                ->get();

            $newResults[] = [
                'product_id' => $productId,
                'units_count' => $data->count(),
                'total_qty' => $data->sum('remaining_qty'),
            ];
        }

        $endNew = microtime(true);
        $newTimeMs = round(($endNew - $startNew) * 1000, 2);

        // الـ Service القديم
        $startOld = microtime(true);
        $oldResults = [];

        foreach ($products as $productId) {
            $service = new \App\Services\MultiProductsInventoryService(
                null,
                $productId,
                'all',
                $storeId,
                false
            );
            $data = $service->getInventoryForProduct($productId);

            $oldResults[] = [
                'product_id' => $productId,
                'units_count' => count($data),
                'total_qty' => array_sum(array_column($data, 'remaining_qty')),
            ];
        }

        $endOld = microtime(true);
        $oldTimeMs = round(($endOld - $startOld) * 1000, 2);

        $improvement = $oldTimeMs > 0 ? round($oldTimeMs / $newTimeMs, 1) : 0;

        return response()->json([
            'success' => true,
            'products_count' => $productsCount,
            'store_id' => $storeId,
            'comparison' => [
                'new_service' => [
                    'name' => 'InventorySummaryReportService',
                    'total_time_ms' => $newTimeMs,
                    'avg_per_product_ms' => round($newTimeMs / $productsCount, 2),
                ],
                'old_service' => [
                    'name' => 'MultiProductsInventoryService',
                    'total_time_ms' => $oldTimeMs,
                    'avg_per_product_ms' => round($oldTimeMs / $productsCount, 2),
                ],
                'improvement' => "{$improvement}x faster",
                'time_saved_ms' => round($oldTimeMs - $newTimeMs, 2),
            ],
            'sample_new' => array_slice($newResults, 0, 3),
            'sample_old' => array_slice($oldResults, 0, 3),
        ]);
    }
}
