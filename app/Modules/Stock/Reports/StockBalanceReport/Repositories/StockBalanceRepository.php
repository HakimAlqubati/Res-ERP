<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\StockBalanceReport\Repositories;

use App\Models\Product;
use App\Modules\Stock\Reports\StockBalanceReport\Contracts\StockBalanceRepositoryInterface;
use App\Modules\Stock\Reports\StockBalanceReport\DataTransferObjects\StockBalanceFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockBalanceRepository implements StockBalanceRepositoryInterface
{
    // ─────────────────────────────────────────────
    //  Public API (الدالة الرئيسية)
    // ─────────────────────────────────────────────

    public function getBalances(StockBalanceFilterDTO $filters): Collection|LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);
        $query = $this->attachInventoryTotals($query, $filters->storeId);

        if ($filters->onlyAvailable) {
            $query = $this->applyAvailabilityFilter($query);
        }

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
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.active',
                'products.category_id',
                'bu.base_unit_name',
                'bu.base_package_size',
                'products.minimum_stock_qty',
            ]);

        // 🔥 التعديل الأول: جلب الوحدة الأساسية باستخدام JOIN ذكي جداً بدل الـ Select المترابط
        $rankedUnits = DB::table('unit_prices as up')
            ->select('up.product_id', 'u.name as base_unit_name', 'up.package_size as base_package_size')
            ->selectRaw('ROW_NUMBER() OVER(PARTITION BY up.product_id ORDER BY up.package_size ASC) as rn')
            ->join('units as u', 'up.unit_id', '=', 'u.id');

        $baseUnits = DB::table($rankedUnits, 'ranked_units')->where('rn', 1);

        $query->leftJoinSub($baseUnits, 'bu', 'bu.product_id', '=', 'products.id');

        // الفلاتر
        if ($filters->onlyActive) {
            $query->where('products.active', true);
        }
        if ($filters->categoryId !== null) {
            $query->where('products.category_id', $filters->categoryId);
        }
        if (!empty($filters->productIds)) {
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
        // 🔥 التعديل الأهم: تجميع الرصيد لكل المنتجات مرة واحدة في الذاكرة المؤقتة لقاعدة البيانات
        $inventoryTotals = DB::table('inventory_transactions')
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN movement_type = 'in' THEN quantity * package_size ELSE 0 END) as total_in")
            ->selectRaw("SUM(CASE WHEN movement_type = 'out' THEN quantity * package_size ELSE 0 END) as total_out")
            ->where('store_id', $storeId)
            ->whereNull('deleted_at')
            ->groupBy('product_id');

        // ربط الجدول المجمع مع جدول المنتجات (أسرع بآلاف المرات)
        $query->leftJoinSub($inventoryTotals, 'inv', 'inv.product_id', '=', 'products.id')
              ->addSelect([
                  DB::raw('COALESCE(inv.total_in, 0) as total_in'),
                  DB::raw('COALESCE(inv.total_out, 0) as total_out'),
                  DB::raw('COALESCE(inv.total_in, 0) - COALESCE(inv.total_out, 0) as remaining_base_qty')
              ]);

        return $query;
    }

    /**
     * فلترة المنتجات التي يتبقى منها رصيد فقط.
     * نستخدم HAVING لأن remaining_base_qty هو عمود محسوب (Alias).
     */
    private function applyAvailabilityFilter(Builder $query): Builder
    {
        // استخدام whereRaw بدلاً من having لأننا الآن نستخدم JOIN صريح
        return $query->whereRaw('(COALESCE(inv.total_in, 0) - COALESCE(inv.total_out, 0)) > 0');
    }

    // ─────────────────────────────────────────────
    //  Public API (الدوال الإضافية)
    // ─────────────────────────────────────────────

    /**
     * جلب تفاصيل الرصيد لمنتج واحد محدد.
     */
    public function getSingleProductBalance(int $productId, int $storeId): ?object
    {
        $query = $this->buildBaseQuery(new StockBalanceFilterDTO(storeId: $storeId, productIds: [$productId]));
        $query = $this->attachInventoryTotals($query, $storeId);
        return $query->first();
    }

    /**
     * جلب المنتجات التي وصل رصيدها للحد الأدنى (النواقص).
     */
   public function getLowStockProducts(int $storeId, ?int $perPage = 15): Collection|LengthAwarePaginator
    {
        $query = $this->buildBaseQuery(new StockBalanceFilterDTO(storeId: $storeId, onlyActive: true));
        $query = $this->attachInventoryTotals($query, $storeId);
        
        $query->whereRaw('(COALESCE(inv.total_in, 0) - COALESCE(inv.total_out, 0)) <= COALESCE(products.minimum_stock_qty, 0)');

        if ($perPage !== null && $perPage > 0) {
            return $query->paginate($perPage);
        }
        return $query->get();
    }
}
