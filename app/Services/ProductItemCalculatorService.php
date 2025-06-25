<?php

namespace App\Services;

use App\Models\ProductItem;

class ProductItemCalculatorService
{
    /**
     * Get components needed to produce a specific quantity of a parent product.
     *
     * @param int $parentProductId
     * @param float $quantity
     * @return array
     */
    public static function calculateComponents(int $parentProductId, float $quantity): array
    {
        $items = ProductItem::where('parent_product_id', $parentProductId)->with(['product', 'unit'])->get();

        $components = [];

        foreach ($items as $item) {
            $baseQty = $item->quantity * $quantity;
            $qtyAfterWaste = ProductItem::calculateQuantityAfterWaste($baseQty, $item->qty_waste_percentage);

            $components[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? '',
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit->name ?? '',
                'base_quantity' => $baseQty,
                'waste_percentage' => $item->qty_waste_percentage,
                'quantity_after_waste' => $qtyAfterWaste,
                'price_per_unit' => $item->price,
                'total_price' => round($qtyAfterWaste * $item->price, 2),
                'parent_id' => $parentProductId,

            ];
        }

        return $components;
    }
}
