<?php

namespace App\Services;

use App\Models\UnitPrice;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\StockInventoryDetail;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Services\MultiProductsInventoryService;

class StockInventoryReportService
{
    public static function getProductsNotInventoriedBetween(
        $startDate,
        $endDate,
        $perPage = 15,
        $storeId = 'all',
        $hideZero = false
    ) {
        // Get stock inventory IDs in the date range and store
        $inventoryQuery = StockInventory::whereBetween('inventory_date', [$startDate, $endDate]);

        if (!empty($storeId) && $storeId !== 'all') {
            $inventoryQuery->where('store_id', $storeId);
        }

        $inventoryIds = $inventoryQuery->pluck('id');

        // Get product IDs that were inventoried
        $productIdsInInventories = StockInventoryDetail::whereIn('stock_inventory_id', $inventoryIds)
            ->pluck('product_id')
            ->unique();

        // Get products that were NOT inventoried
        $productsCollection = Product::whereNotIn('id', $productIdsInInventories)->get();

        // Transform each product with inventory data
        $transformed = $productsCollection->transform(function ($product) {
            $store = defaultManufacturingStore($product);
            $storeId = $store?->id;

            if (!$storeId) {
                $product->remaining_qty = 0;
                $product->remaining_qty_in_smallest_unit = 0;
                $product->smallest_unit_name = '';
                $product->store_name = '—';
            } else {
                $smallestUnit = UnitPrice::where('product_id', $product->id)
                    ->with('unit')
                    ->orderBy('package_size', 'asc')
                    ->first();

                $service = new MultiProductsInventoryService(
                    null,
                    $product->id,
                    $smallestUnit?->unit_id,
                    $storeId
                );

                $inventoryData = $service->getInventoryForProduct($product->id);
                $remainingQty = $inventoryData[0]['remaining_qty'] ?? 0;

                $product->remaining_qty = $remainingQty;
                $product->store_name = $store->name ?? '—';
                $product->smallest_unit_name = $smallestUnit?->unit?->name ?? '';
            }

            return $product;
        });

        // Filter out products with 0 quantity if requested
        if ($hideZero) {
            $transformed = $transformed->filter(fn($product) => $product->remaining_qty != 0)->values();
        }

        // Manual pagination
        $page = request('page', 1);
        $paginated = new LengthAwarePaginator(
            $transformed->forPage($page, $perPage),
            $transformed->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginated;
    }
}