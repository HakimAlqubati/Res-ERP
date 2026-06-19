<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Repositories;

use App\Models\Product;
use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StockBalanceRepository implements StockBalanceRepositoryInterface
{
    // ─────────────────────────────────────────────
    //  Public API (الدالة الرئيسية)
    // ─────────────────────────────────────────────

    public function getBalances(StockBalanceFilterDTO $filters): Collection|LengthAwarePaginator
    {
        // 1. بناء الاستعلام الأساسي للمنتجات والفلاتر
        $query = $this->buildBaseQuery($filters);

        // 2. إرفاق حسابات المخزون (الداخل، الخارج، والرصيد المتبقي بالوحدة الأساسية)
        $query = $this->attachInventoryTotals($query, $filters->storeId);

        // 3. فلترة المنتجات المتوفرة فقط (إذا طلب المستخدم ذلك)
        if ($filters->onlyAvailable) {
            $query = $this->applyAvailabilityFilter($query);
        }

        // 4. تنفيذ الاستعلام وإعادة النتائج (بالصفحات أو كدفعة واحدة)
        if ($filters->wantsPagination()) {
            return $query->paginate($filters->perPage);
        }

        return $query->get();
    }

    // ─────────────────────────────────────────────
    //  Private Helpers (الدوال المساعدة)
    // ─────────────────────────────────────────────

    /**
     * بناء استعلام المنتجات الأساسي وتطبيق الفلاتر البسيطة.
     */
    private function buildBaseQuery(StockBalanceFilterDTO $filters): Builder
    {
        // نختار فقط الأعمدة التي نحتاجها من جدول المنتجات لتقليل استهلاك الذاكرة
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.active',
                'products.category_id',
            ]);

        if ($filters->onlyActive) {
            $query->where('products.active', true);
        }

        if ($filters->categoryId !== null) {
            $query->where('products.category_id', $filters->categoryId);
        }

        if (! empty($filters->productIds)) {
            $query->whereIn('products.id', $filters->productIds);
        }

        return $query;
    }

    /**
     * إرفاق حسابات المخزون باستخدام استعلامات فرعية (Subqueries) سريعة جداً.
     * هذا يغنينا عن عمل حلقات تكرار (foreach) وعمليات حسابية في لغة PHP.
     */
    private function attachInventoryTotals(Builder $query, int $storeId): Builder
    {
        // استعلام لجلب مجموع الحركات الواردة (بالوحدة الأساسية)
        $inSql = "(
            SELECT COALESCE(SUM(quantity * package_size), 0) 
            FROM inventory_transactions 
            WHERE product_id = products.id 
              AND movement_type = 'in' 
              AND store_id = ? 
              AND deleted_at IS NULL
        )";

        // استعلام لجلب مجموع الحركات الصادرة (بالوحدة الأساسية)
        $outSql = "(
            SELECT COALESCE(SUM(quantity * package_size), 0) 
            FROM inventory_transactions 
            WHERE product_id = products.id 
              AND movement_type = 'out' 
              AND store_id = ? 
              AND deleted_at IS NULL
        )";

        // دمج الاستعلامات للحصول على: إجمالي الوارد، إجمالي الصادر، والرصيد المتبقي
        $query->selectRaw("{$inSql} as total_in", [$storeId])
            ->selectRaw("{$outSql} as total_out", [$storeId])
            ->selectRaw("({$inSql} - {$outSql}) as remaining_base_qty", [$storeId, $storeId]);

        return $query;
    }

    /**
     * فلترة المنتجات التي يتبقى منها رصيد فقط.
     * نستخدم HAVING لأن remaining_base_qty هو عمود محسوب (Alias).
     */
    private function applyAvailabilityFilter(Builder $query): Builder
    {
        // بدلاً من جلب البيانات للـ PHP لفلترتها، نطلب من قاعدة البيانات
        // إعادة المنتجات التي رصيدها أكبر من الصفر فقط!
        return $query->having('remaining_base_qty', '>', 0);
    }

    // ─────────────────────────────────────────────
    //  Public API (الدوال الإضافية)
    // ─────────────────────────────────────────────

    /**
     * جلب تفاصيل الرصيد لمنتج واحد محدد.
     */
    public function getSingleProductBalance(int $productId, int $storeId): ?object
    {
        // 1. تحديد المنتج
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.active',
            ])
            ->where('products.id', $productId);

        // 2. إرفاق حسابات المخزون (الداخل، الخارج، الرصيد المتبقي بالوحدة الأساسية)
        $query = $this->attachInventoryTotals($query, $storeId);

        // 3. إعادة كائن واحد فقط
        return $query->first();
    }

    /**
     * جلب المنتجات التي وصل رصيدها للحد الأدنى (النواقص).
     */
    public function getLowStockProducts(int $storeId, ?int $perPage = 15): Collection|LengthAwarePaginator
    {
        // 1. استعلام المنتجات النشطة فقط (لا داعي لطلب نواقص لمنتج متوقف)
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.minimum_stock_qty' // نفترض وجود هذا العمود لتحديد الحد الأدنى
            ])
            ->where('products.active', true);

        // 2. إرفاق حسابات الرصيد الفعلي في المخزن المحدد
        $query = $this->attachInventoryTotals($query, $storeId);

        // 3. السر هنا: نطلب من قاعدة البيانات إرجاع المنتجات التي رصيدها 
        // أصغر من أو يساوي الحد الأدنى للطلب (بدون نقلها للـ PHP)
        $query->havingRaw('remaining_base_qty <= COALESCE(products.minimum_stock_qty, 0)');

        // 4. تنفيذ وإعادة النتائج
        if ($perPage !== null && $perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }
}
