<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\UnitPrice;
use App\Services\OrderDetailsPriceUpdaterByFifo;
use App\Services\ProductItemCalculatorService;
use App\Services\ProductUnitConversionService;
use App\Services\UnitPriceFifoUpdater;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController7 extends Controller
{
    public function updatePriceUsingFifo()
    {
        $results = [];
        $products = Product::active()->unmanufacturingCategory()->select('id', 'name')->get();
        ProductPriceHistory::truncate();
        foreach ($products as $product) {
            $updates = UnitPriceFifoUpdater::updatePriceUsingFifo($product->id);

            if (!empty($updates)) {
                $results[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name ?? null,
                    'updated_units' => $updates,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث الأسعار بناءً على FIFO',
            'updated_products_count' => count($results),
            'data' => $results,
        ]);
    }

    public static function getOrderOutTransactions()
    {
        $ids = InventoryTransaction::select('id')
            ->where('transactionable_type', \App\Models\PurchaseInvoice::class)
            ->pluck('id');

        return InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->select('transactionable_id', 'product_id',  'unit_id', 'package_size', 'price', 'notes', 'source_transaction_id')
            ->where('transactionable_type', \App\Models\Order::class)
            ->whereIn('source_transaction_id', $ids)
            ->get();
    }

    public function fixOrderPrices()
    {
        $result = OrderDetailsPriceUpdaterByFifo::updateAll();

        return response()->json([
            'status' => 'done',
            'updated_count' => count($result),
            'updated_items' => $result,
        ]);
    }



    function getOverConsumedSuppliesReport(): array
    {
        $overConsumed = [];

        // نحصل على الـ package size المستهدف للتحويل إليه
        $unitPriceTarget = UnitPrice::where('unit_id', $targetUnitId)
            ->first();

        if (!$unitPriceTarget) {
            throw new \Exception("الوحدة غير موجودة أو لا تملك package size معرف.");
        }

        $targetPackageSize = floatval($unitPriceTarget->package_size ?? 1);

        // كل الحركات IN
        $inTransactions = InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->get();

        foreach ($inTransactions as $inTransaction) {
            $entryPackageSize = floatval($inTransaction->package_size ?? 1);

            // نحول الكمية إلى الوحدة المستهدفة (مثل زجاجة)
            $inQtyInTargetUnit = ($inTransaction->quantity * $entryPackageSize) / $targetPackageSize;

            // نأخذ كل الحركات OUT المرتبطة بهذا الإدخال
            $outTransactions = InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('source_transaction_id', $inTransaction->id)
                ->get();

            $outTotalInTargetUnit = 0;

            foreach ($outTransactions as $out) {
                $outPackageSize = floatval($out->package_size ?? 1);
                $outTotalInTargetUnit += ($out->quantity * $outPackageSize) / $targetPackageSize;
            }

            if ($outTotalInTargetUnit > $inQtyInTargetUnit) {
                $overConsumed[] = [
                    'product_id' => $inTransaction->product_id,
                    'supply_transaction_id' => $inTransaction->id,
                    'store_id' => $inTransaction->store_id,
                    'original_unit_id' => $inTransaction->unit_id,
                    'target_unit_id' => $targetUnitId,
                    'quantity_in_original' => $inTransaction->quantity,
                    'package_size_in' => $entryPackageSize,
                    'quantity_in_target_unit' => round($inQtyInTargetUnit, 2),
                    'quantity_out_target_unit' => round($outTotalInTargetUnit, 2),
                    'over_consumed_by' => round($outTotalInTargetUnit - $inQtyInTargetUnit, 2),
                    'movement_date' => $inTransaction->movement_date,
                    'notes' => $inTransaction->notes,
                ];
            }
        }

        return $overConsumed;
    }


    public function updateUnitPrices(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'from_unit_id' => 'required|integer|exists:units,id',
            'to_unit_id' => 'required|integer|exists:units,id',
            'from_package_size' => 'required|numeric|min:0',
            'to_package_size' => 'required|numeric|min:0',
        ]);

        try {
            $service = new ProductUnitConversionService();
            $service->migrateProductUnitAndPackageSize(
                $validated['product_id'],
                $validated['from_unit_id'],
                $validated['to_unit_id'],
                $validated['from_package_size'],
                $validated['to_package_size']
            );

            return response()->json([
                'status' => 'success',
                'message' => '✅ تم تحديث unit_id و package_size بنجاح لجميع الجداول.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '❌ حدث خطأ أثناء التحديث: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function updateUnitIdAndPackageSizeForProduct(
        $productId,
        $fromUnitId,
        $toUnitId,
        $fromPackageSize,
        $toPackageSize
    ): void {
        DB::transaction(function () use ($productId, $fromUnitId, $toUnitId, $fromPackageSize, $toPackageSize) {
            // unit_prices
            DB::table('unit_prices')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);

            // purchase_invoice_details
            DB::table('purchase_invoice_details')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);

            // orders_details
            DB::table('orders_details')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);

            // goods_received_note_details
            DB::table('goods_received_note_details')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);

            // stock_supply_order_details
            DB::table('stock_supply_order_details')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);

            // stock_issue_order_details
            DB::table('stock_issue_order_details')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);

            // inventory_transactions
            DB::table('inventory_transactions')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);
            DB::table('stock_inventory_details')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);


            // 9. Update stock_adjustment_details (Stock Adjustment Records)
            DB::table('stock_adjustment_details')
                ->where('product_id', $productId)
                ->where('unit_id', $fromUnitId)
                ->where('package_size', $fromPackageSize)
                ->update([
                    'unit_id' => $toUnitId,
                    'package_size' => $toPackageSize,
                ]);
            logger("✅ Updated unit_id and package_size for product #$productId from unit $fromUnitId (pkg $fromPackageSize) to unit $toUnitId (pkg $toPackageSize)");
        });
    }

    public function getComponentsData(Request $request)
    {
        $parentProductId = $request->input('parent_product_id');
        $quantity = $request->input('quantity', 1); // default to 1 if not provided

        if (!$parentProductId) {
            return response()->json(['error' => 'parent_product_id is required'], 400);
        }

        $components = ProductItemCalculatorService::calculateComponents((int)$parentProductId, (float)$quantity);

        return response()->json([
            'success' => true,
            'parent_product_id' => $parentProductId,
            'quantity_requested' => $quantity,
            'components' => $components,
        ]);
    }
}
