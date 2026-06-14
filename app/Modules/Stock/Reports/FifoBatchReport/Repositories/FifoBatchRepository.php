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
                'products.name as product_name',
                'products.code as product_code',
                'units.name as unit_name',
                DB::raw('COALESCE(consumed.total_consumed_base, 0) / inventory_transactions.package_size as consumed_qty'),
                DB::raw('GREATEST(0, inventory_transactions.quantity - COALESCE(consumed.total_consumed_base, 0) / inventory_transactions.package_size) as remaining_qty'),
            ])
            ->join('products', 'products.id', '=', 'inventory_transactions.product_id')
            ->join('units', 'units.id', '=', 'inventory_transactions.unit_id')
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
            ->when($filter->productId, fn($q, $v) => $q->where('inventory_transactions.product_id', $v))
            ->when($filter->unitId, fn($q, $v) => $q->where('inventory_transactions.unit_id', $v))
            ->when($filter->storeId, fn($q, $v) => $q->where('inventory_transactions.store_id', $v))
            ->when($filter->dateFrom, fn($q, $v) => $q->where('inventory_transactions.movement_date', '>=', $v))
            ->when($filter->dateTo, fn($q, $v) => $q->where('inventory_transactions.movement_date', '<=', $v))
            ->orderBy('inventory_transactions.id')
            ->get();
    }
}
