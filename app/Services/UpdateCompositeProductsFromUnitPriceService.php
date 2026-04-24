<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\ProductPriceHistory;
use App\Models\UnitPrice;

class UpdateCompositeProductsFromUnitPriceService
{
    public function handle(
        int $unitPriceId,
        int $productId,
        int $unitId,
        float $newPrice,
    ): void {
        $componentProduct = UnitPrice::query()
            ->whereKey($unitPriceId)
            ->with(['product:id,name,code'])
            ->first()
            ?->product;

        $componentLabel = $componentProduct
            ? trim(($componentProduct->code ? $componentProduct->code . ' - ' : '') . $componentProduct->name)
            : ('Product #' . $productId);

        $parentProductIds = [];

        ProductItem::query()
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$parentProductIds, $componentLabel, $unitPriceId, $newPrice) {
                $historyRows = [];
                $now = now();

                foreach ($items as $item) {
                    $oldPrice = (float) $item->price;

                    $parentProductIds[] = (int) $item->parent_product_id;

                    if (round($oldPrice, 8) === round($newPrice, 8)) {
                        continue;
                    }

                    $historyRows[] = [
                        'product_id' => $item->parent_product_id,
                        'product_item_id' => $item->id,
                        'unit_id' => $item->unit_id,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'source_type' => UnitPrice::class,
                        'source_id' => $unitPriceId,
                        'note' => 'Updated because component product price changed: ' . $componentLabel,
                        'date' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $item->price = $newPrice;
                    $item->total_price = round($newPrice * (float) $item->quantity, 8);
                    $item->total_price_after_waste = ProductItem::calculateTotalPriceAfterWaste(
                        $item->total_price,
                        (float) ($item->qty_waste_percentage ?? 0)
                    );
                    $item->save();
                }

                if (! empty($historyRows)) {
                    ProductPriceHistory::insert($historyRows);
                }
            });

        foreach (array_unique(array_filter($parentProductIds)) as $parentProductId) {
            ProductCostingService::recalculateManufacturingProductFromItems((int) $parentProductId);
        }
    }
}
