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
        $category = $product->category;

        if (! $category) {
            return null;
        }

        /* 1) إن كانت الفئة مربوطة بفرع محدد -> استخدم مخزن الفرع (حتى لو ليست تصنيعية) */

        // حالة (A): الفئة لديها عمود branch_id
        if (!empty($category->branch_id)) {
            $branch = Branch::query()
                ->whereKey($category->branch_id)
                ->whereNotNull('store_id')
                ->first();

            if ($branch) {
                return $branch->store;
            }
        }

        // حالة (B): علاقة many-to-many بين الفروع والفئات (branches <-> categories)
        // إن كانت لديك هذه العلاقة على موديل Branch: public function categories()
        $branchWithThisCategory = Branch::query()
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            })
            ->whereNotNull('store_id')
            ->first();

        if ($branchWithThisCategory) {
            return $branchWithThisCategory->store;
        }

        /* 2) إن كانت الفئة تصنيعية ولم نجد فرعًا محددًا لها:
          حاول إيجاد مطبخ مركزي مرتبط بنفس الفئة، ثم مطبخ مركزي عام بدون فئات */
        if ($category->is_manafacturing) {
            // مطبخ مركزي مرتبط بالفئة
            $central = Branch::centralKitchens()
                ->whereHas('categories', function ($q) use ($category) {
                    $q->where('categories.id', $category->id);
                })
                ->whereNotNull('store_id')
                ->first();

            if ($central) {
                return $central->store;
            }

            // مطبخ مركزي عام بدون فئات
            $fallbackCentral = Branch::centralKitchens()
                ->whereDoesntHave('categories')
                ->whereNotNull('store_id')
                ->first();

            if ($fallbackCentral) {
                return $fallbackCentral->store;
            }
        }

        /* 3) فئة غير تصنيعية ولم تكن مربوطة بفرع -> ارجع المخزن الافتراضي العام */
        return Store::defaultStore();
    }
}
