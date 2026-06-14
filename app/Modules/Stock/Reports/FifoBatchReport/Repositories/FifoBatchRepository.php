<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Repositories;

use App\Models\InventoryTransaction;
use App\Modules\Stock\Reports\FifoBatchReport\Contracts\FifoBatchRepositoryInterface;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FifoBatchRepository implements FifoBatchRepositoryInterface
{
    public function getBatchesWithConsumption(FifoBatchFilterDTO $filter): Collection
    {
        return InventoryTransaction::query()
            ->select([
                'inventory_transactions.id',
                'inventory_transactions.product_id',
                'inventory_transactions.unit_id',
                'inventory_transactions.store_id',
                'inventory_transactions.quantity',
                'inventory_transactions.package_size',
                'inventory_transactions.price',
                'inventory_transactions.movement_date',
                'inventory_transactions.transactionable_type',
                'inventory_transactions.transactionable_id',
                'inventory_transactions.base_unit_id',
                'products.name as product_name',
                'products.code as product_code',
                'units.name as unit_name',
                'base_units.name as base_unit_name',
                DB::raw('(inventory_transactions.quantity * inventory_transactions.package_size) as base_entry_qty'),
                DB::raw('COALESCE(consumed.total_consumed_base, 0) as base_consumed_qty'),
                DB::raw('(inventory_transactions.quantity * inventory_transactions.package_size) - COALESCE(consumed.total_consumed_base, 0) as base_remaining_qty'),
                DB::raw('inventory_transactions.price / inventory_transactions.package_size as base_price'),
            ])
            ->join('products', 'products.id', '=', 'inventory_transactions.product_id')
            ->join('units', 'units.id', '=', 'inventory_transactions.unit_id')
            ->leftJoin('units as base_units', 'base_units.id', '=', 'inventory_transactions.base_unit_id')
            ->leftJoinSub(
                InventoryTransaction::query()
                    ->select([
                        'source_transaction_id',
                        DB::raw('SUM(quantity * package_size) as total_consumed_base'),
                    ])
                    ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                    ->whereNull('deleted_at')
                    ->groupBy('source_transaction_id'),
                'consumed',
                'consumed.source_transaction_id',
                '=',
                'inventory_transactions.id'
            )
            ->where('inventory_transactions.movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('inventory_transactions.deleted_at')
            ->when($filter->productIds, fn($q, $v) => $q->whereIn('inventory_transactions.product_id', $v))
            ->when($filter->unitId, fn($q, $v) => $q->where('inventory_transactions.unit_id', $v))
            ->when($filter->storeId, fn($q, $v) => $q->where('inventory_transactions.store_id', $v))
            ->when($filter->dateFrom, fn($q, $v) => $q->where('inventory_transactions.movement_date', '>=', $v))
            ->when($filter->dateTo, fn($q, $v) => $q->where('inventory_transactions.movement_date', '<=', $v))
            ->when($filter->excludeDepleted, fn($q) => $q->havingRaw('base_remaining_qty > 0'))
            ->orderBy('inventory_transactions.id')
            ->get();
    }
}
