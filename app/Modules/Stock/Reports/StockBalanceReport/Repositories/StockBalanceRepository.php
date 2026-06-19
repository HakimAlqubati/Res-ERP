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
    public function getBalances(StockBalanceFilterDTO $filters): Collection|LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);
        $query = $this->attachInventoryTotals($query, $filters->storeId);

        if ($filters->onlyAvailable) {
            $query = $this->applyAvailabilityFilter($query, $filters->storeId);
        }

        if ($filters->wantsPagination()) {
            return $query->paginate($filters->perPage);
        }

        return $query->get();
    }

    public function getSingleProductBalance(int $productId, int $storeId): ?object
    {
        $query = $this->buildBaseQuery(new StockBalanceFilterDTO(storeId: $storeId, productIds: [$productId]));
        $query = $this->attachInventoryTotals($query, $storeId);
        return $query->first();
    }

    public function getLowStockProducts(int $storeId, ?int $perPage = 15): Collection|LengthAwarePaginator
    {
        $query = $this->buildBaseQuery(new StockBalanceFilterDTO(storeId: $storeId, onlyActive: true));
        $query = $this->attachInventoryTotals($query, $storeId);
        
        // فلترة النواقص برمجياً كشرط داخل قاعدة البيانات
        $inSql = "SELECT COALESCE(SUM(quantity * package_size), 0) FROM inventory_transactions WHERE product_id = products.id AND movement_type = 'in' AND store_id = {$storeId} AND deleted_at IS NULL";
        $outSql = "SELECT COALESCE(SUM(quantity * package_size), 0) FROM inventory_transactions WHERE product_id = products.id AND movement_type = 'out' AND store_id = {$storeId} AND deleted_at IS NULL";
        
        $query->whereRaw("({$inSql}) - ({$outSql}) <= COALESCE(products.minimum_stock_qty, 0)");

        if ($perPage !== null && $perPage > 0) {
            return $query->paginate($perPage);
        }
        return $query->get();
    }

    // ─────────────────────────────────────────────
    //  Private Helpers (الدوال المساعدة السريعة)
    // ─────────────────────────────────────────────

    private function buildBaseQuery(StockBalanceFilterDTO $filters): Builder
    {
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.active',
                'products.category_id',
                'products.minimum_stock_qty',
            ])
           ->with('smallestReportUnit.unit');
        // // استعلامات فرعية سريعة (Subquery Selects)
        // $query->addSelect([
        //     'base_unit_id' => DB::table('unit_prices')
        //         ->whereColumn('unit_prices.product_id', 'products.id')
        //         ->where('unit_prices.usage_scope', '!=', 'manufacturing_only')
        //         ->orderBy('unit_prices.package_size', 'asc')
        //         ->select('unit_prices.unit_id')
        //         ->limit(1),

        //     'base_unit_name' => DB::table('unit_prices')
        //         ->join('units', 'units.id', '=', 'unit_prices.unit_id')
        //         ->whereColumn('unit_prices.product_id', 'products.id')
        //         ->where('unit_prices.usage_scope', '!=', 'manufacturing_only')
        //         ->orderBy('unit_prices.package_size', 'asc')
        //         ->select('units.name')
        //         ->limit(1),

        //     'base_package_size' => DB::table('unit_prices')
        //         ->whereColumn('unit_prices.product_id', 'products.id')
        //         ->where('unit_prices.usage_scope', '!=', 'manufacturing_only')
        //         ->orderBy('unit_prices.package_size', 'asc')
        //         ->select('unit_prices.package_size')
        //         ->limit(1),
        // ]);

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

    private function attachInventoryTotals(Builder $query, int $storeId): Builder
    {
        // حسابات الداخل والخارج بدون Joins
        $query->addSelect([
            'total_in' => DB::table('inventory_transactions')
                ->selectRaw('COALESCE(SUM(quantity * package_size), 0)')
                ->whereColumn('product_id', 'products.id')
                ->where('store_id', $storeId)
                ->where('movement_type', 'in')
                ->whereNull('deleted_at'),

            'total_out' => DB::table('inventory_transactions')
                ->selectRaw('COALESCE(SUM(quantity * package_size), 0)')
                ->whereColumn('product_id', 'products.id')
                ->where('store_id', $storeId)
                ->where('movement_type', 'out')
                ->whereNull('deleted_at'),
        ]);

        return $query;
    }

    private function applyAvailabilityFilter(Builder $query, int $storeId): Builder
    {
        $inSql = "SELECT COALESCE(SUM(quantity * package_size), 0) FROM inventory_transactions WHERE product_id = products.id AND movement_type = 'in' AND store_id = ? AND deleted_at IS NULL";
        $outSql = "SELECT COALESCE(SUM(quantity * package_size), 0) FROM inventory_transactions WHERE product_id = products.id AND movement_type = 'out' AND store_id = ? AND deleted_at IS NULL";

        return $query->whereRaw("({$inSql}) - ({$outSql}) > 0",[$storeId,$storeId]);
    }
}