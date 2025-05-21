<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
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
}
