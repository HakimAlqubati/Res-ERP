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

        // fromSub تُغلِّف الـ subquery كـ derived table محل CTE
        $query = DB::table($stockBatches, 'stock_batches')
            ->selectRaw('*, (base_unit_in_qty - base_unit_out) AS current_stock')
            ->selectRaw('((base_unit_in_qty - base_unit_out) * unit_price) AS remaining_total_price')
            ->selectRaw('CASE WHEN ROW_NUMBER() OVER(
            PARTITION BY product_id,
            CASE WHEN (base_unit_in_qty - base_unit_out) > 0 THEN 1 ELSE 0 END
             ORDER BY id ASC
             ) = 1 
              AND (base_unit_in_qty - base_unit_out) > 0
                THEN true ELSE false END AS is_current_batch')
            // ->selectRaw('CONCAT(REGEXP_REPLACE(SUBSTRING_INDEX(transactionable_type, "\\\\", -1), "([a-z])([A-Z])", "$1 $2"), " #", transactionable_id) AS source_document')
            ->selectRaw('CONCAT(transactionable_id, " #", transactionable_type) AS source_document')
            // ->whereRaw('(base_unit_in_qty - base_unit_out) > 0')
            // ->orWhereRaw('(base_unit_in_qty - base_unit_out) < 0');
            ->whereRaw('(base_unit_in_qty - base_unit_out) != 0');

        if ($isCurrentBatch !== null) {
            $query = DB::table($query, 'filtered_batches')
                ->where('is_current_batch', $isCurrentBatch ? 1 : 0);
        } 
        $finalResult = $query->orderBy('id')->get();

        // return $finalResult;
        return $this->adjustNegativeBatches($finalResult);
    }

    /**
     * إذا كان هناك باتش برصيد سالب، يتم خصمه من الباتش الموجب الذي يليه مباشرة
     * ثم يتم حذف الباتش السالب من النتائج
     */
    private function adjustNegativeBatches(Collection $batches): Collection
    {
        $batches = $batches->values();
        $toRemove = [];

        for ($i = 0; $i < $batches->count(); $i++) {
            $batch = $batches[$i];

            if ((float) $batch->current_stock < 0) {
                $deficit = abs((float) $batch->current_stock);

                // Find the next positive batch to absorb the deficit
                for ($j = $i + 1; $j < $batches->count(); $j++) {
                    if ((float) $batches[$j]->current_stock > 0) {
                        $batches[$j]->current_stock = (string) ((float) $batches[$j]->current_stock - $deficit);
                        $batches[$j]->remaining_total_price = (string) ((float) $batches[$j]->current_stock * (float) $batches[$j]->unit_price);
                        $toRemove[] = $i;
                        break;
                    }
                }
            }
        }

        // Remove absorbed negative batches and re-index
        $adjusted = $batches->filter(fn ($item, $key) => ! in_array($key, $toRemove))->values();

        // Recalculate is_current_batch: first positive batch per product_id
        $seenProducts = [];
        foreach ($adjusted as $batch) {
            if ((float) $batch->current_stock > 0 && ! isset($seenProducts[$batch->product_id])) {
                $batch->is_current_batch = 1;
                $seenProducts[$batch->product_id] = true;
            } else {
                $batch->is_current_batch = 0;
            }
        }

        return $adjusted;
    }

    private function outAggregatesSubquery(): Builder
    {
        return DB::table(self::TABLE)
            ->select('source_transaction_id')
            ->selectRaw('SUM(quantity * package_size) AS total_out_qty')
            ->where('movement_type', 'out')
            ->whereNull('deleted_at')
            ->groupBy('source_transaction_id');
    }

    private function baseUnitsSubquery(): Builder
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
            ]);

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
                $this->outAggregatesSubquery(),
                'oa',
                'oa.source_transaction_id',
                '=',
                'in_t.id'
            )
            ->leftJoinSub(
                $this->baseUnitsSubquery(),
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
