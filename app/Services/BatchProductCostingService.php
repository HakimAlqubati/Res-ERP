<?php

namespace App\Services;

use App\Models\Product;

class BatchProductCostingService
{
    /**
     * Update component prices for multiple manufacturing products.
     *
     * @param array $productIds
     * @return int عدد العناصر التي تم تحديثها
     */
    public static function updateComponentPricesForMany(array $productIds): int
    {
        $updatedCount = 0;

        // تحميل كل المنتجات المركبة المطلوبة مع علاقاتها لتقليل الاستعلامات
        $products = Product::with(['productItems', 'unitPrices'])
            ->whereIn('id', $productIds)
            ->manufacturingCategory()
            ->get();

        foreach ($products as $product) {
            $updatedCount += ProductCostingService::updateComponentPricesForProductInstance($product);
        }

        return $updatedCount;
    }
}
