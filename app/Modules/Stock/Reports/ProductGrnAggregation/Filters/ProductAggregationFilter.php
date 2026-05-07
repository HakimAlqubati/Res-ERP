<?php

namespace App\Modules\Stock\Reports\ProductGrnAggregation\Filters;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Carbon\Carbon;

class ProductAggregationFilter
{
    /**
     * Apply smart filters to the Eloquent Builder (Used for fetching Products).
     */
    public function applyToEloquent(EloquentBuilder $query, array $filters): EloquentBuilder
    {
        $query->whereHas('inventoryTransactions', function ($itQuery) use ($filters) {
            $itQuery->where('movement_type', 'in')
                    ->where('transactionable_type', \App\Models\GoodsReceivedNote::class)
                    ->join('goods_received_notes as grn', 'grn.id', '=', 'inventory_transactions.transactionable_id')
                    ->whereNull('grn.deleted_at');
            
            if (!empty($filters['date_from'])) {
                $itQuery->whereDate('grn.grn_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $itQuery->whereDate('grn.grn_date', '<=', $filters['date_to']);
            }
            if (!empty($filters['store_id'])) {
                $itQuery->where('grn.store_id', $filters['store_id']);
            }
            if (!empty($filters['supplier_id'])) {
                $itQuery->where('grn.supplier_id', $filters['supplier_id']);
            }
            if (!empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $itQuery->where(function($q) use ($search) {
                    $q->where('grn.grn_number', 'like', $search)
                      ->orWhere('grn.notes', 'like', $search);
                });
            }
        });

        if (!empty($filters['product_id'])) {
            $productIds = is_array($filters['product_id']) ? $filters['product_id'] : [$filters['product_id']];
            $query->whereIn('id', $productIds);
        }

        return $query;
    }

    /**
     * Apply filters to the raw DB Builder (Used for Aggregations).
     */
    public function applyToRaw(Builder $query, array $filters): Builder
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('grn.grn_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('grn.grn_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['store_id'])) {
            $query->where('grn.store_id', $filters['store_id']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('grn.supplier_id', $filters['supplier_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('grn.grn_number', 'like', $search)
                  ->orWhere('grn.notes', 'like', $search);
            });
        }
        return $query;
    }
}
