<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\UnitPrice;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\Log;

class ManufacturedProductPriceUpdaterService
{
    /**
     * تحديث أسعار المنتجات المركبة بناءً على أسعار المكونات من unit_prices مباشرة.
     */
    public function updateAll(): void
    {
        $products = Product::with('productItems', 'unitPrices')->unmanufacturingCategory()->get();

        foreach ($products as $product) {
            $this->updateSingle($product);
        }
    }

    /**
     * تحديث منتج مركب واحد.
     *
     * @param Product $product
     */
    public function updateSingle(Product|int $product): ?array
    {
        if (is_int($product)) {
            $product = Product::with('productItems', 'unitPrices')->find($product);
        }

        if (!$product || !$product->is_manufacturing || $product->productItems->isEmpty()) {
            return null;
        }

        $totalNetPrice = 0;
        $items = [];

        foreach ($product->productItems as $item) {
            $price = UnitPrice::where('product_id', $item->product_id)
                ->where('unit_id', $item->unit_id)
                ->value('price');

            if (is_null($price)) {
                continue;
            }

            $items[] = [
                'component_product_id' => $item->product_id,
                'component_unit_id'    => $item->unit_id,
                'old_price'            => $item->price,
                'new_price'            => $price,
            ];

            $item->price = $price;
            $item->total_price = $price * $item->quantity;
            $item->total_price_after_waste = ProductItem::calculateTotalPriceAfterWaste(
                $item->total_price,
                $item->qty_waste_percentage ?? 0
            );
            $item->save();

            $totalNetPrice += $item->total_price_after_waste;
            ProductPriceHistory::create([
                'product_id'      => $product->id,
                'product_item_id' => $item->id,
                'unit_id'         => $item->unit_id,
                'old_price'       => $item->price,
                'new_price'       => $price,
                'source_type'     => 'DirectUnitPrice',
                'source_id'       => $item->product_id,
                'note'            => 'Updated from direct unit price (manufactured product)',
                'date'            => now(),
            ]);
        }

        $updatedUnits = [];

        foreach ($product->unitPrices as $unitPrice) {
            $old = $unitPrice->price;
            $unitPrice->price = round($unitPrice->package_size * $totalNetPrice, 2);
            $unitPrice->save();

            $updatedUnits[] = [
                'unit_id' => $unitPrice->unit_id,
                'package_size' => $unitPrice->package_size,
                'old_price' => $old,
                'new_price' => $unitPrice->price,
            ];
            ProductPriceHistory::create([
                'product_id'  => $product->id,
                'unit_id'     => $unitPrice->unit_id,
                'old_price'   => $old,
                'new_price'   => $unitPrice->price,
                'source_type' => 'FinalProductCosting',
                'source_id'   => $product->id,
                'note'        => 'Final price from manufactured items',
                'date'        => now(),
            ]);
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'components_updated' => $items,
            'unit_prices_updated' => $updatedUnits,
        ];
    }
}
