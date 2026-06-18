<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Repositories;

// use App\DataTransferObjects\StockBatchData;
use App\Models\UnitPrice;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class InventoryStockRepository implements InventoryStockRepositoryInterface
{
    private const TABLE = 'inventory_transactions';

    public function getAvailableStockBatches(?int $productId, int $storeId, ?bool $isCurrentBatch = null): Collection
    {
        $stockBatches = $this->stockBatchesSubquery($productId, $storeId);

        // الطبقة 1: حساب الرصيد الخام + المجموع التراكمي لكل منتج
        $withRunningTotal = DB::table($stockBatches, 'stock_batches')
            ->selectRaw('*')
            ->selectRaw('(base_unit_in_qty - base_unit_out) AS current_stock')
            ->selectRaw('SUM(base_unit_in_qty - base_unit_out) OVER (PARTITION BY product_id ORDER BY id) AS running_total')
            ->whereRaw('(base_unit_in_qty - base_unit_out) != 0');

        // الطبقة 2: حساب أعلى مجموع تراكمي سابق لكل صف
        $withMaxPrev = DB::table($withRunningTotal, 'rt')
            ->selectRaw('*')
            ->selectRaw('COALESCE(MAX(running_total) OVER (PARTITION BY product_id ORDER BY id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) AS max_prev_rt');

        // الطبقة 3: تصفية الباتشات الموجبة التي يبقى لها رصيد بعد استيعاب العجز
        $filtered = DB::table($withMaxPrev, 'mp')
            ->selectRaw('*')
            ->whereRaw('current_stock > 0')
            ->whereRaw('GREATEST(0, running_total - GREATEST(0, max_prev_rt)) > 0');

        // الطبقة 4: النتيجة النهائية مع الرصيد المعدّل و is_current_batch
        $query = DB::table($filtered, 'final_batches')
            ->selectRaw('id, product_id, product, transactionable_type, transactionable_id, movement_date')
            ->selectRaw('unit, in_qty, package_size, base_unit, base_unit_package_size, price')
            ->selectRaw('base_unit_in_qty, base_unit_out, unit_price')
            ->selectRaw('GREATEST(0, running_total - GREATEST(0, max_prev_rt)) AS current_stock')
            ->selectRaw('GREATEST(0, running_total - GREATEST(0, max_prev_rt)) * unit_price AS remaining_total_price')
            ->selectRaw('CASE WHEN ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY id) = 1 THEN 1 ELSE 0 END AS is_current_batch')
            ->selectRaw('CONCAT(transactionable_id, " #", transactionable_type) AS source_document');

        if ($isCurrentBatch !== null) {
            $query = DB::table($query, 'filtered_batches')
                ->where('is_current_batch', $isCurrentBatch ? 1 : 0);
        }

        return $query->orderBy('product_id', 'asc')->orderBy('id')->get();
    }

    private function outAggregatesSubquery(int $storeId): Builder
    {
        return DB::table(self::TABLE)
            ->select('source_transaction_id')
            ->selectRaw('SUM(quantity * package_size) AS total_out_qty')
            ->where('movement_type', 'out')
            ->where('store_id', $storeId)
            ->whereNull('deleted_at')
            ->groupBy('source_transaction_id');
    }

    private function baseUnitsSubquery(?int $productId): Builder
    {
        $ranked = DB::table('unit_prices as up')
            ->select('up.product_id', 'u.name as base_unit_name', 'up.package_size as base_package_size')
            ->selectRaw('ROW_NUMBER() OVER(PARTITION BY up.product_id ORDER BY up.package_size ASC) as rn')
            ->join('units as u', 'up.unit_id', '=', 'u.id')
            ->whereIn('up.usage_scope', [
                UnitPrice::USAGE_ALL,
                UnitPrice::USAGE_SUPPLY_ONLY,
                UnitPrice::USAGE_OUT_ONLY,
                UnitPrice::USAGE_NONE,
            ])
            ->when($productId, fn ($q) => $q->where('up.product_id', $productId));

        return DB::table($ranked, 'ranked_units')->where('rn', 1);
    }

    private function stockBatchesSubquery(?int $productId, int $storeId): Builder
    { 
        return DB::table(self::TABLE.' AS in_t')
            ->select([
                // 1. Transaction & Product Info
                'in_t.id',
                'in_t.product_id',
                'p.name as product',
                'in_t.transactionable_type',
                'in_t.transactionable_id',
                'in_t.movement_date',
                
                // 2. Original IN Unit Info
                'in_t.unit_id',
                'u.name as unit',
                'in_t.quantity as in_qty',
                'in_t.package_size',
                
                // 3. Base Unit Info
                'bu.base_unit_name as base_unit',
                'bu.base_package_size as base_unit_package_size',
                
                // 4. Financial (Original)
                'in_t.price',
            ])
            // 5. Quantities in Base Unit
            ->selectRaw('(in_t.quantity * in_t.package_size) AS base_unit_in_qty')
            ->selectRaw('COALESCE(oa.total_out_qty, 0) AS base_unit_out')
            // 6. Pricing per Base Unit
            ->selectRaw('(in_t.price / in_t.package_size) as unit_price')
            ->leftJoinSub(
                $this->outAggregatesSubquery($storeId),
                'oa',
                'oa.source_transaction_id',
                '=',
                'in_t.id'
            )
            ->leftJoinSub(
                $this->baseUnitsSubquery($productId),
                'bu',
                'bu.product_id',
                '=',
                'in_t.product_id'
            )
            ->join('products AS p', 'in_t.product_id', '=', 'p.id')
            ->join('units AS u', 'in_t.unit_id', '=', 'u.id')
            ->where('in_t.movement_type', 'in')
            ->when($productId, fn ($query) => $query->where('in_t.product_id', $productId))
            ->where('in_t.store_id', $storeId)
            ->whereNull('in_t.deleted_at');
    }
}
