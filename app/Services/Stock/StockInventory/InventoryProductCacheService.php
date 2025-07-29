<?php

namespace App\Services\Stock\StockInventory;

use Illuminate\Support\Facades\Cache;
use App\Models\Product;

class InventoryProductCacheService
{
    // مدة الكاش الافتراضية (10 دقائق)
    const CACHE_TTL = 600;
    // عدد نتائج البحث القصوى لكل استعلام
    const MAX_RESULTS = 15;
    // اسم الكاش لجميع المنتجات النشطة
    const ALL_PRODUCTS_CACHE_KEY = 'inventory_active_products_all';

    /**
     * جلب أول 5 منتجات افتراضية لواجهة البحث، مرتبة حسب id من الأصغر للأكبر
     */
    public static function getDefaultOptions()
    {
        return Cache::remember('inventory_products_default_options', self::CACHE_TTL, function () {
            return Product::where('active', 1)
                ->orderBy('id')
                ->limit(5)
                ->get(['id', 'name', 'code']);
        });
    }

    /**
     * البحث عن المنتجات النشطة مع كاش ديناميكي لكل كلمة بحث
     */
    public static function search($search)
    {
        $trimmed = trim(mb_strtolower($search));
        $cacheKey = 'inventory_products_search_' . md5($trimmed);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($trimmed) {
            return Product::where('active', 1)
                ->where(function ($query) use ($trimmed) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$trimmed}%"])
                          ->orWhereRaw('LOWER(code) LIKE ?', ["%{$trimmed}%"]);
                })
                ->orderBy('id')
                ->limit(self::MAX_RESULTS)
                ->get(['id', 'name', 'code']);
        });
    }

    /**
     * حفظ جميع المنتجات النشطة دفعة واحدة في الكاش (إذا لم يكن الكاش موجود)
     */
    public static function cacheAllActiveProducts()
    {
        // إذا الكاش موجود بالفعل، لا تفعل شيئًا
        if (Cache::has(self::ALL_PRODUCTS_CACHE_KEY)) {
            return;
        }
        $products = Product::where('active', 1)
            ->orderBy('id')
            ->get(['id', 'name', 'code']);

        Cache::put(self::ALL_PRODUCTS_CACHE_KEY, $products, self::CACHE_TTL);
    }

    /**
     * جلب جميع المنتجات النشطة من الكاش (أو من القاعدة إذا لم يوجد الكاش)
     */
    public static function getAllActiveProducts()
    {
        return Cache::remember(self::ALL_PRODUCTS_CACHE_KEY, self::CACHE_TTL, function () {
            return Product::where('active', 1)
                ->orderBy('id')
                ->get(['id', 'name', 'code']);
        });
    }

    /**
     * يمكنك إضافة دوال لمسح الكاش عند الحاجة إذا أردت
     */
    public static function clearAllCache()
    {
        Cache::forget('inventory_products_default_options');
        Cache::forget(self::ALL_PRODUCTS_CACHE_KEY);
        // لحذف كاش البحث ينصح استخدام prefix أو tags لو كنت تستخدم Redis
    }
}