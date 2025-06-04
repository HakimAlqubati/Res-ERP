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
        $products = Product::with('productItems', 'unitPrices')->manufacturingCategory()->get();

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

    public function updateSingleHistorilly(Product|int $product): ?array
    {
        if (is_int($product)) {
            $product = Product::with('productItems', 'unitPrices')->find($product);
        }

        if (!$product || !$product->is_manufacturing || $product->productItems->isEmpty()) {
            return null;
        }

        $items = [];
        $updatedUnits = [];

        // حذف كل السجلات التاريخية القديمة للمنتج المركب
        ProductPriceHistory::where('product_id', $product->id)->delete();

        // أضف أول سعر من ExcelImport (كالمعتاد)
        foreach ($product->unitPrices as $unitPrice) {
            $firstInventoryTransaction = \App\Models\InventoryTransaction::where('product_id', $product->id)
                ->where('unit_id', $unitPrice->unit_id)
                ->where('transactionable_type', 'ExcelImport')
                ->orderBy('movement_date', 'ASC')
                ->first();

            if ($firstInventoryTransaction) {
                ProductPriceHistory::create([
                    'product_id'  => $product->id,
                    'unit_id'     => $unitPrice->unit_id,
                    'old_price'   => null,
                    'new_price'   => $firstInventoryTransaction->price,
                    'source_type' => 'ExcelImport',
                    'source_id'   => $firstInventoryTransaction->id,
                    'note'        => 'First price imported from ExcelImport transaction',
                    'date'        => $firstInventoryTransaction->movement_date,
                ]);
            }
        }

        // تحويل التاريخين إلى كائنات Carbon
        $start = \Carbon\Carbon::parse('2025-05-01')->startOfDay();
        $end = \Carbon\Carbon::parse(now()->toDateString())->endOfDay();

        // دورة عبر كل يوم
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateString = $date->toDateString();

            $hasAnyUpdate = false;

            // تحديث المكونات في هذا اليوم
            foreach ($product->productItems as $item) {
                $history = ProductPriceHistory::where('product_id', $item->product_id)
                    ->where('unit_id', $item->unit_id)
                    ->whereNull('product_item_id')
                    ->whereDate('date', $dateString)
                    ->first();

                if ($history) {
                    $price = $history->new_price;

                    // تحديث بيانات المكون
                    $item->price = $price;
                    $item->total_price = $price * $item->quantity;
                    $item->total_price_after_waste = ProductItem::calculateTotalPriceAfterWaste(
                        $item->total_price,
                        $item->qty_waste_percentage ?? 0
                    );
                    $item->save();

                    // سجل تحديث المكون
                    $items[] = [
                        'date'                 => $dateString,
                        'component_product_id' => $item->product_id,
                        'component_unit_id'    => $item->unit_id,
                        'old_price'            => $item->price,
                        'new_price'            => $price,
                    ];

                    // سجل تاريخ السعر للمكون
                    ProductPriceHistory::create([
                        'product_id'      => $product->id,
                        'product_item_id' => $item->id,
                        'unit_id'         => $item->unit_id,
                        'old_price'       => $item->price,
                        'new_price'       => $price,
                        'source_type'     => 'DirectUnitPrice',
                        'source_id'       => $item->product_id,
                        'note'            => 'Updated from direct unit price (manufactured product)',
                        'date'            => $dateString,
                    ]);

                    $hasAnyUpdate = true;
                }
            }

            if ($hasAnyUpdate) {
                // إذا كان هناك أي تعديل في هذا اليوم ➜ احسب السعر النهائي للمنتج المركب
                $totalNetPrice = $product->productItems->sum(function ($item) {
                    $totalPrice = $item->price * $item->quantity;
                    return ProductItem::calculateTotalPriceAfterWaste($totalPrice, $item->qty_waste_percentage ?? 0);
                });

                // تحديث وحدات المنتج المركب وتسجيلها
                foreach ($product->unitPrices as $unitPrice) {
                    $old = $unitPrice->price;
                    $newPrice = round($unitPrice->package_size * $totalNetPrice, 2);
                    $unitPrice->price = $newPrice;
                    $unitPrice->save();
                    ProductPriceHistory::create([
                        'product_id'  => $product->id,
                        'unit_id'     => $unitPrice->unit_id,
                        'old_price'   => $old,
                        'new_price'   => $newPrice,
                        'source_type' => 'FinalProductCosting',
                        'source_id'   => $product->id,
                        'note'        => 'Final price from manufactured items (historical record)',
                        'date'        => $dateString,
                    ]);

                    // احفظ السعر النهائي فعليًا فقط في اليوم الأخير
                    if ($date->eq($end)) {
                        $unitPrice->price = $newPrice;
                        $unitPrice->save();

                        $updatedUnits[] = [
                            'unit_id'      => $unitPrice->unit_id,
                            'package_size' => $unitPrice->package_size,
                            'old_price'    => $old,
                            'new_price'    => $newPrice,
                        ];
                    }
                }
            }
        }

        return [
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'components_updated'  => $items,
            'unit_prices_updated' => $updatedUnits,
        ];
    }
}
