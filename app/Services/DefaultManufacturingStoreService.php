<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Store;

class DefaultManufacturingStoreService
{
    /**
     * Get the default store for a manufacturing product
     */
    public function getDefaultStoreForProduct(Product $product): ?Store
    {

        if ($product->category_id && !$product->category->is_manafacturing) {
            return Store::defaultStore();
        }
        if (
            $product->category_id &&
            $product->category->is_manafacturing
        ) {
            // نبحث عن فرع مرتبط بنفس فئة المنتج
            $branch = Branch::centralKitchens()
                ->whereHas('categories', function ($query) use ($product) {
                    $query->where('categories.id', $product->category_id);
                })
                ->whereNotNull('store_id')
                ->first();

            if ($branch) {
                return $branch->store;
            }
        }

        // لو لم نجد فرع مرتبط بالفئة، نبحث عن فرع بدون فئات
        if (
            $product->category_id &&
            $product->category->is_manafacturing
        ) {
            $branch = Branch::centralKitchens()
                ->whereDoesntHave('categories')
                ->whereNotNull('store_id')
                ->first();

            if ($branch) {
                return $branch->store;
            }
        }

        return null;
    }
}
