<?php

namespace App\Http\Controllers\Api\Developer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockIssueOrder;
use App\Models\StockSupplyOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Developer Inventory Controller
 * توريد وصرف المخزون
 * ⚠️ للتطوير فقط
 */
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
            $order = StockSupplyOrder::create([
                'order_date' => $request->order_date,
                'store_id' => $request->store_id,
                'notes' => $request->notes ?? 'Dev: Stock Supply',
            ]);

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
            $order = StockIssueOrder::create([
                'order_date' => $request->order_date,
                'store_id' => $request->store_id,
                'notes' => $request->notes ?? 'Dev: Stock Issue',
            ]);

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
     * إنشاء فاتورة مشتريات وهمية بمنتجات عشوائية
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

            $supplierId = \App\Models\Supplier::first()?->id;

            $invoice = \App\Models\PurchaseInvoice::create([
                'date' => now()->toDateString(),
                'supplier_id' => $supplierId,
                'store_id' => $storeId,
                'invoice_no' => \App\Models\PurchaseInvoice::autoInvoiceNo(),
                'description' => 'Dev: Random Purchase Invoice with ' . $products->count() . ' products',
            ]);

            $detailsCreated = 0;

            foreach ($products as $product) {
                $unitPrice = $product->unitPrices()->first();

                if (!$unitPrice) {
                    continue;
                }

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
}
