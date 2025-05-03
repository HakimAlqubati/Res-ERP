<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockInventory;
use App\Models\StockInventoryDetail;
use Illuminate\Support\Carbon;

class StockInventoryReportService
{
    public static function getProductsNotInventoriedBetween($startDate, $endDate, $perPage = 15)
    {
        $inventoryIds = StockInventory::whereBetween('inventory_date', [$startDate, $endDate])
            ->pluck('id');

        $productIdsInInventories = StockInventoryDetail::whereIn('stock_inventory_id', $inventoryIds)
            ->pluck('product_id')
            ->unique();

        // Return products NOT in inventory details
        return Product::whereNotIn('id', $productIdsInInventories)->paginate($perPage);
    }
}
