<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockInventory;
use App\Models\StockInventoryDetail;
use Illuminate\Support\Carbon;

class StockInventoryReportService
{
    public static function getProductsNotInventoriedBetween(
        $startDate,
        $endDate,
        $perPage = 15,
        $storeId = 'all',
        $hideZero = false
    ) {
        $inventoryQuery = StockInventory::whereBetween('inventory_date', [$startDate, $endDate]);

        if (!empty($storeId) && $storeId !== 'all') {
            $inventoryQuery->where('store_id', $storeId);
        }

        $inventoryIds = $inventoryQuery->pluck('id');
        $productIdsInInventories = StockInventoryDetail::whereIn('stock_inventory_id', $inventoryIds)
            ->pluck('product_id')
            ->unique();

        // Get products NOT in inventory details
        $productsQuery = Product::whereNotIn('id', $productIdsInInventories);

        // Use pagination
        $products = $productsQuery->paginate($perPage);

        // Add remaining_qty and smallest unit qty+name to each product
        $transformed = $products->getCollection()->transform(function ($product) {
            $store = defaultManufacturingStore($product);
            $storeId = $store?->id;

            if (!$storeId) {
                $product->remaining_qty = 0;
                $product->remaining_qty_in_smallest_unit = 0;
                $product->smallest_unit_name = '';
            } else {
                $smallestUnit =    \App\Models\UnitPrice::where('product_id', $product->id)
                    ->with('unit')
                    ->orderBy('package_size', 'asc')
                    ->first();
                $service = new MultiProductsInventoryService(
                    null,
                    $product->id,
                    $smallestUnit->unit_id,
                    $storeId
                );

                $inventoryData = $service->getInventoryForProduct($product->id);

                // Using first unit data for remaining_qty
                $remainingQty = $inventoryData[0]['remaining_qty'] ?? 0;

                // Find the smallest unit data from inventoryData
                $smallestUnitData = collect($inventoryData)
                    ->sortBy('package_size')
                    ->first();


                // Assign to product
                $product->remaining_qty = $remainingQty;
                $product->store_name = $store?->name ?? '—';
                $product->smallest_unit_name = $smallestUnit->unit->name;;
            }

            return $product;
        });
        if ($hideZero) {
            $transformed = $transformed->filter(fn($product) => $product->remaining_qty != 0)->values();
            $products->setCollection($transformed);
        }
        return $products;
    }
}