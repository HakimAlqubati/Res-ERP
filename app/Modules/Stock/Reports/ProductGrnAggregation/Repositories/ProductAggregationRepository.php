<?php

namespace App\Modules\Stock\Reports\ProductGrnAggregation\Repositories;

use App\Models\Product;
use App\Models\GoodsReceivedNote;
use App\Models\InventoryTransaction;
use App\Modules\Stock\Reports\ProductGrnAggregation\Contracts\ProductAggregationRepositoryInterface;
use App\Modules\Stock\Reports\ProductGrnAggregation\Filters\ProductAggregationFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductAggregationRepository implements ProductAggregationRepositoryInterface
{
    public function __construct(
        private readonly ProductAggregationFilter $filter
    ) {}

    public function getPaginatedProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();
        $query = $this->filter->applyToEloquent($query, $filters);
        
        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getInboundAggregations(array $productIds, array $filters = []): array
    {
        if (empty($productIds)) {
            return [];
        }

        $query = DB::table('inventory_transactions as it')
            ->join('goods_received_notes as grn', 'it.transactionable_id', '=', 'grn.id')
            ->leftJoin('units', 'it.unit_id', '=', 'units.id')
            ->where('it.transactionable_type', GoodsReceivedNote::class)
            ->where('it.movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('it.deleted_at')
            ->whereNull('grn.deleted_at')
            ->whereIn('it.product_id', $productIds);

        $query = $this->filter->applyToRaw($query, $filters);

        // SUM(quantity * package_size) gives the exact Base Quantity entered
        return $query->select(
                'it.product_id', 
                DB::raw('MAX(units.name) as unit_name'), 
                DB::raw('MAX(it.package_size) as package_size'), 
                DB::raw('SUM(it.quantity * it.package_size) as total_in')
            )
            ->groupBy('it.product_id')
            ->get()
            ->keyBy('product_id')
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function getOutboundAggregations(array $productIds, array $filters = []): array
    {
        if (empty($productIds)) {
            return [];
        }

        $query = DB::table('inventory_transactions as out_tx')
            ->join('inventory_transactions as it', 'out_tx.source_transaction_id', '=', 'it.id')
            ->join('goods_received_notes as grn', 'it.transactionable_id', '=', 'grn.id')
            ->where('it.transactionable_type', GoodsReceivedNote::class)
            ->where('it.movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('out_tx.movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->whereNull('out_tx.deleted_at')
            ->whereNull('it.deleted_at')
            ->whereNull('grn.deleted_at')
            ->whereIn('it.product_id', $productIds);

        $query = $this->filter->applyToRaw($query, $filters);

        // SUM(quantity * package_size) for OUT transactions gives the exact Base Quantity consumed
        return $query->select('it.product_id', DB::raw('SUM(out_tx.quantity * out_tx.package_size) as total_out'))
            ->groupBy('it.product_id')
            ->pluck('total_out', 'it.product_id')
            ->toArray();
    }
}
