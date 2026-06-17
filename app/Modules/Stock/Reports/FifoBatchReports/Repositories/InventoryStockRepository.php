<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Repositories;

// use App\DataTransferObjects\StockBatchData;
 use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\InventoryStockRepositoryInterface;

final class InventoryStockRepository implements InventoryStockRepositoryInterface
{
    private const TABLE = 'inventory_transactions';

    public function getAvailableStockBatches(?int $productId, int $storeId, ?bool $isCurrentBatch = null): Collection
    {
        $stockBatches = $this->stockBatchesSubquery($productId, $storeId);

        // dd($stockBatches->get());
        // fromSub تُغلِّف الـ subquery كـ derived table محل CTE
        $query = DB::table($stockBatches, 'stock_batches')
            ->selectRaw('*, (total_in - total_out) AS current_stock')
            ->selectRaw('((total_in - total_out) * unit_price) AS remaining_total_price')
            ->selectRaw('CASE WHEN ROW_NUMBER() OVER(PARTITION BY product_id ORDER BY id ASC) = 1 THEN true ELSE false END AS is_current_batch')
            ->selectRaw('CONCAT(REGEXP_REPLACE(SUBSTRING_INDEX(transactionable_type, "\\\\", -1), "([a-z])([A-Z])", "$1 $2"), " #", transactionable_id) AS source_document')
            ->whereRaw('(total_in - total_out) > 0');

        if ($isCurrentBatch !== null) {
            $query = DB::table($query, 'filtered_batches')
                ->where('is_current_batch', $isCurrentBatch ? 1 : 0);
        }

        return $query->orderBy('id')->get();
    }

      private function outAggregatesSubquery(): Builder
    {
        return DB::table(self::TABLE)
            ->select('source_transaction_id')
            ->selectRaw('SUM(quantity * package_size) AS total_out_qty,MAX(package_size) AS out_package_size')
            ->where('movement_type', 'out')
            ->whereNull('deleted_at')
            ->groupBy('source_transaction_id');
    }

     private function stockBatchesSubquery(?int $productId, int $storeId): Builder
    {
        // dd($this
        // ->outAggregatesSubquery()->get()[0]);
        return DB::table(self::TABLE . ' AS in_t')
            ->select([
                'in_t.id',
                'in_t.price',
                'in_t.product_id',
                'p.name as product',
                'u.name as unit',
                'in_t.package_size',
                'in_t.transactionable_type',
                'in_t.transactionable_id',
                'in_t.movement_date',
            ])
            ->selectRaw('(in_t.price / in_t.package_size) as unit_price')
            ->selectRaw('(in_t.quantity * in_t.package_size) AS total_in')
            ->selectRaw('COALESCE(oa.total_out_qty, 0) AS total_out')
            ->selectRaw('COALESCE(oa.out_package_size, 0) AS out_package_size')
            ->leftJoinSub(
                $this->outAggregatesSubquery(),
                'oa',
                'oa.source_transaction_id',
                '=',
                'in_t.id'
            )
            ->join('products AS p', 'in_t.product_id', '=', 'p.id')
            ->join('units AS u', 'in_t.unit_id', '=', 'u.id')
            ->where('in_t.movement_type', 'in')
            ->when($productId, fn($query) => $query->where('in_t.product_id', $productId))
            ->where('in_t.store_id', $storeId)
            ->whereNull('in_t.deleted_at');
    }
}
 