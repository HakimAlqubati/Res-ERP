<?php

namespace App\Observers;

use App\Models\ProductItem;
use App\Models\ProductPriceHistory;
use App\Models\UnitPrice;
use App\Services\ProductCostingService;
use Illuminate\Support\Facades\DB;

class UnitPriceObserver
{
    public function updated(UnitPrice $unitPrice): void
    {
        if (! $unitPrice->wasChanged('price')) {
            return;
        }

        DB::afterCommit(function () use ($unitPrice) {
            $matchingItems = ProductItem::query()
                ->where('product_id', $unitPrice->product_id)
                ->where('unit_id', $unitPrice->unit_id)
                ->get();

            if ($matchingItems->isEmpty()) {
                return;
            }

            $parentProductIds = [];

            foreach ($matchingItems as $item) {
                $oldPrice = (float) $item->price;
                $newPrice = (float) $unitPrice->price;

                if (round($oldPrice, 8) === round($newPrice, 8)) {
                    $parentProductIds[] = $item->parent_product_id;
                    continue;
                }

                ProductPriceHistory::create([
                    'product_id' => $item->parent_product_id,
                    'product_item_id' => $item->id,
                    'unit_id' => $item->unit_id,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'source_type' => UnitPrice::class,
                    'source_id' => $unitPrice->id,
                    'note' => 'Updated from UnitPrice observer',
                    'date' => now(),
                ]);

                $item->price = $newPrice;
                $item->total_price = round($newPrice * (float) $item->quantity, 8);
                $item->total_price_after_waste = ProductItem::calculateTotalPriceAfterWaste(
                    $item->total_price,
                    (float) ($item->qty_waste_percentage ?? 0)
                );
                $item->save();

                $parentProductIds[] = $item->parent_product_id;
            }

            foreach (array_unique(array_filter($parentProductIds)) as $parentProductId) {
                ProductCostingService::recalculateManufacturingProductFromItems((int) $parentProductId);
            }
        });
    }
}
