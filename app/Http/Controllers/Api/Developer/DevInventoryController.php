<?php

namespace App\Http\Controllers\Api\Developer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockIssueOrder;
use App\Models\StockSupplyOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevInventoryController extends Controller
{
    /**
     * توريد مخزني للمطور
     */
    public function stockSupply(Request $request): JsonResponse
    {
        $request->validate([
            'order_date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.package_size' => 'nullable|numeric|min:1',
            'details.*.price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // إنشاء أمر التوريد
            $order = StockSupplyOrder::create([
                'order_date' => $request->order_date,
                'store_id' => $request->store_id,
                'notes' => $request->notes ?? 'Dev: Stock Supply',
            ]);

            // إنشاء تفاصيل التوريد
            foreach ($request->details as $detail) {
                $product = Product::find($detail['product_id']);
                $unitPrice = $product->unitPrices()
                    ->where('unit_id', $detail['unit_id'])
                    ->first();

                $packageSize = $detail['package_size'] ?? $unitPrice?->package_size ?? 1;

                $order->details()->create([
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'package_size' => $packageSize,
                    'price' => $detail['price'] ?? $unitPrice?->price ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock supply order created successfully',
                'data' => $order->load('details'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * صرف مخزني للمطور
     */
    public function stockIssue(Request $request): JsonResponse
    {
        $request->validate([
            'order_date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.unit_id' => 'required|exists:units,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.package_size' => 'nullable|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // إنشاء أمر الصرف
            $order = StockIssueOrder::create([
                'order_date' => $request->order_date,
                'store_id' => $request->store_id,
                'notes' => $request->notes ?? 'Dev: Stock Issue',
            ]);

            // إنشاء تفاصيل الصرف
            foreach ($request->details as $detail) {
                $product = Product::find($detail['product_id']);
                $unitPrice = $product->unitPrices()
                    ->where('unit_id', $detail['unit_id'])
                    ->first();

                $packageSize = $detail['package_size'] ?? $unitPrice?->package_size ?? 1;

                $order->details()->create([
                    'product_id' => $detail['product_id'],
                    'unit_id' => $detail['unit_id'],
                    'quantity' => $detail['quantity'],
                    'package_size' => $packageSize,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock issue order created successfully',
                'data' => $order->load('details'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء فاتورة مشتريات وهمية بـ 300 منتج عشوائي
     * 
     * POST /api/dev/randomPurchase
     * Body: { "store_id": 1, "count": 300 }
     */
    public function randomPurchase(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'count' => 'nullable|integer|min:1|max:500',
        ]);

        $storeId = $request->store_id;
        $count = $request->input('count', 300);

        DB::beginTransaction();
        try {
            // جلب منتجات عشوائية لها unitPrices
            $products = Product::where('active', 1)
                ->where('type', '!=', Product::TYPE_FINISHED_POS)
                ->whereHas('unitPrices')
                ->inRandomOrder()
                ->limit($count)
                ->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products with unit prices found',
                ], 400);
            }

            // جلب أول مورد
            $supplierId = \App\Models\Supplier::first()?->id;

            // إنشاء الفاتورة
            $invoice = \App\Models\PurchaseInvoice::create([
                'date' => now()->toDateString(),
                'supplier_id' => $supplierId,
                'store_id' => $storeId,
                'invoice_no' => \App\Models\PurchaseInvoice::autoInvoiceNo(),
                'description' => 'Dev: Random Purchase Invoice with ' . $products->count() . ' products',
            ]);

            $detailsCreated = 0;

            foreach ($products as $product) {
                // اختيار أول unitPrice للمنتج
                $unitPrice = $product->unitPrices()->first();

                if (!$unitPrice) {
                    continue;
                }

                // كمية عشوائية بين 1 و 100
                $quantity = rand(1, 100);

                $invoice->details()->create([
                    'product_id' => $product->id,
                    'unit_id' => $unitPrice->unit_id,
                    'quantity' => $quantity,
                    'price' => $unitPrice->price ?? rand(10, 500),
                    'package_size' => $unitPrice->package_size ?? 1,
                    'waste_stock_percentage' => $product->waste_stock_percentage ?? 0,
                ]);

                $detailsCreated++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Purchase invoice created with {$detailsCreated} products",
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'store_id' => $storeId,
                    'products_count' => $detailsCreated,
                    'date' => $invoice->date,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Benchmark للمقارنة بين Service القديم والجديد
     * 
     * POST /api/dev/benchmarkSummary
     * Body: { "store_id": 1, "count": 100, "use_new": true }
     */
    public function benchmarkSummary(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'count' => 'nullable|integer|min:1|max:1000',
            'use_new' => 'nullable|boolean',
        ]);

        $storeId = (int) $request->store_id;
        $count = $request->input('count', 100);
        $useNew = $request->input('use_new', true); // افتراضي: الجديد

        // بدء احتساب الوقت
        $startTime = microtime(true);

        // جلب منتجات عشوائية
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
                // الـ Service الجديد
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
                // الـ Service القديم
                $service = new \App\Services\MultiProductsInventoryService(
                    null,           // categoryId
                    $productId,     // productId
                    'all',          // unitId
                    $storeId,       // storeId
                    false           // filterOnlyAvailable
                );
                $data = $service->getInventoryForProduct($productId);

                $results[] = [
                    'product_id' => $productId,
                    'units_count' => count($data),
                    'total_qty' => array_sum(array_column($data, 'remaining_qty')),
                ];
            }
        }

        // نهاية احتساب الوقت
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
     * POST /api/dev/benchmarkCompare
     * Body: { "store_id": 1, "count": 100 }
     */
    public function benchmarkCompare(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'count' => 'nullable|integer|min:1|max:500',
        ]);

        $storeId = (int) $request->store_id;
        $count = $request->input('count', 100);

        // جلب نفس المنتجات لكليهما
        $products = Product::where('active', 1)
            ->where('type', '!=', Product::TYPE_FINISHED_POS)
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();

        $productsCount = count($products);

        // ═══════════════════════════════════════════════════════════════
        // اختبار الـ Service الجديد
        // ═══════════════════════════════════════════════════════════════
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

        // ═══════════════════════════════════════════════════════════════
        // اختبار الـ Service القديم
        // ═══════════════════════════════════════════════════════════════
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

        // ═══════════════════════════════════════════════════════════════
        // حساب التحسن
        // ═══════════════════════════════════════════════════════════════
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
