<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
}
