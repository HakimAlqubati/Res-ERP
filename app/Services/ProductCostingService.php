<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\UnitPrice;

class ProductCostingService
{
    /**
     * Get the effective cost of a product based on FIFO logic,
     * or fallback to last purchase price if no quantity is available.
     *
     * @param int $productId
     * @param int $unitId
     * @return float|null
     */
    public static function getEffectiveProductCostFIFOOrLast(int $productId, int $unitId): ?float
    {
        $targetUnitPrice = UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        $targetPackageSize = $targetUnitPrice?->package_size ?? 1;

        $entriesIn = InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();

        $totalIn = $entriesIn->sum(fn($e) => $e->quantity * $e->package_size);

        $entriesOut = InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->whereNull('deleted_at')
            ->get();

        $totalOut = $entriesOut->sum(fn($e) => $e->quantity * $e->package_size);

        $remainingQty = $totalIn - $totalOut;

        if ($remainingQty > 0) {
            foreach ($entriesIn as $entry) {
                $entryQtyInBase = $entry->quantity * $entry->package_size;
                if ($entryQtyInBase <= $totalOut) {
                    $totalOut -= $entryQtyInBase;
                    continue;
                } else {
                    $pricePerBaseUnit = $entry->price / ($entry->package_size ?: 1);
                    $res = round($pricePerBaseUnit * $targetPackageSize, 6);
                    return $res;
                }
            }
        }

        $lastEntry = InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if ($lastEntry && $lastEntry->package_size) {
            $pricePerBaseUnit = $lastEntry->price / $lastEntry->package_size;
            $res = round($pricePerBaseUnit * $targetPackageSize, 6);
            return $res;
        }

        return null;
    }

    public static function updateComponentPricesForProduct(int $productId): int
    {
        $product = \App\Models\Product::with('productItems')->find($productId);

        if (!$product || !$product->is_manufacturing) {
            return 0;
        }

        $updatedCount = 0;

        foreach ($product->productItems as $item) {
            $price = self::getEffectiveProductCostFIFOOrLast(
                $item->product_id,
                $item->unit_id
            );

            $transaction = self::getInventoryTransactionForCost($item->product_id, $item->unit_id, $price);

            \App\Models\ProductPriceHistory::create([
                'product_id'       => $product->id,
                'product_item_id'  => $item->id,
                'unit_id'          => $item->unit_id,
                'old_price'        => $item->price,
                'new_price'        => $price,
                'source_type'      => $transaction?->transactionable_type,
                'source_id'        => $transaction?->transactionable_id,
                'note'             => 'Auto update during costing process',
            ]);

            if (!is_null($price)) {
                $item->price = $price;
                $item->total_price = $price * $item->quantity;
                $item->total_price_after_waste = \App\Models\ProductItem::calculateTotalPriceAfterWaste($item->total_price, $item->qty_waste_percentage ?? 0);
                $item->save();

                $updatedCount++;
            }
        }

        // Update unit prices after updating all component prices
        $finalPrice = $product->productItems->sum('total_price_after_waste') ?? 0;

        foreach ($product->unitPrices as $unitPrice) {
            $packageSize = $unitPrice->package_size ?: 1;
            $unitPrice->price = round($packageSize * $finalPrice, 2);
            $unitPrice->save();
        }

        return $updatedCount;
    }

    public static function getInventoryTransactionForCost(int $productId, int $unitId, float $price): ?\App\Models\InventoryTransaction
    {
        return \App\Models\InventoryTransaction::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
            ->where('price', $price)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();
    }
}
