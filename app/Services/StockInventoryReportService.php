<?php

namespace App\Services;

use App\Models\InventorySummary;
use App\Models\Product;
use App\Models\Store;
use App\Models\StockInventory;
use App\Models\StockInventoryDetail;
use App\Models\UnitPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockInventoryReportService
{
    public static function getProductsNotInventoriedBetween(
        $startDate,
        $endDate,
        $perPage = 15,
        $storeId = null,
        $hideZero = false
    ): LengthAwarePaginator {
        // 1. جلب IDs للمخزون في النطاق الزمني والمخزن المحدد
        $inventoryQuery = StockInventory::whereBetween('inventory_date', [$startDate, $endDate]);

        if ($storeId) {
            $inventoryQuery->where('store_id', $storeId);
        }

        $inventoryIds = $inventoryQuery->pluck('id');

        // 2. جلب IDs المنتجات المجردة
        $inventoriedProductIds = StockInventoryDetail::whereIn('stock_inventory_id', $inventoryIds)
            ->pluck('product_id')
            ->unique();

        // 3. بناء Query للمنتجات غير المجردة مع Eager Loading
        $query = Product::query()
            ->whereNotIn('id', $inventoriedProductIds)
            ->with(['category:id,name']);

        // 4. الحصول على النتائج مع Pagination من قاعدة البيانات
        $products = $query->paginate($perPage);

        // 5. جلب بيانات المخزون لكل المنتجات دفعة واحدة
        $productIds = $products->pluck('id')->toArray();

        if (empty($productIds)) {
            return $products;
        }

        // جلب أصغر وحدة لكل منتج دفعة واحدة
        $smallestUnits = UnitPrice::query()
            ->select(['product_id', 'unit_id'])
            ->whereIn('product_id', $productIds)
            ->with('unit:id,name')
            ->orderBy('package_size', 'asc')
            ->get()
            ->groupBy('product_id')
            ->map(fn($units) => $units->first());

        // جلب الكميات من InventorySummary مرة واحدة
        $inventorySummaries = InventorySummary::query()
            ->select(['product_id', 'unit_id', 'remaining_qty', 'store_id'])
            ->whereIn('product_id', $productIds)
            ->where('store_id', $storeId)
            ->with('unit:id,name')
            ->get()
            ->groupBy('product_id');

        // جلب اسم المخزن مرة واحدة
        $storeName = Store::find($storeId)?->name ?? '—';

        // 6. معالجة كل منتج وإضافة بيانات المخزون
        $products->getCollection()->transform(function ($product) use ($inventorySummaries, $smallestUnits, $storeName) {
            $summaries = $inventorySummaries->get($product->id);
            $smallestUnit = $smallestUnits->get($product->id);

            if ($summaries && $summaries->isNotEmpty()) {
                // إذا كان هناك عدة وحدات، نستخدم أصغر وحدة
                $summary = $summaries->first();

                // إذا كانت أصغر وحدة موجودة، نحاول إيجاد السماري الخاص بها
                if ($smallestUnit) {
                    $matchingSummary = $summaries->firstWhere('unit_id', $smallestUnit->unit_id);
                    if ($matchingSummary) {
                        $summary = $matchingSummary;
                    }
                }

                $product->remaining_qty = $summary->remaining_qty ?? 0;
                $product->smallest_unit_name = $summary->unit?->name ?? ($smallestUnit?->unit?->name ?? '');
            } else {
                $product->remaining_qty = 0;
                $product->smallest_unit_name = $smallestUnit?->unit?->name ?? '';
            }

            $product->store_name = $storeName;

            return $product;
        });

        // 7. فلترة المنتجات بكمية صفر إذا لزم الأمر
        if ($hideZero) {
            $filtered = $products->getCollection()->filter(fn($p) => $p->remaining_qty > 0);
            $products->setCollection($filtered->values());
        }

        return $products;
    }
}
