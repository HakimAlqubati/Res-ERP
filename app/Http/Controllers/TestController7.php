<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\UnitPrice;
use App\Services\OrderDetailsPriceUpdaterByFifo;
use App\Services\UnitPriceFifoUpdater;
use Illuminate\Http\Request;

class TestController7 extends Controller
{
    public function updatePriceUsingFifo()
    {
        $results = [];
        $products = Product::active()->unmanufacturingCategory()->select('id', 'name')->get();

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
}
