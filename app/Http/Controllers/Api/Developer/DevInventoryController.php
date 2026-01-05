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
}
