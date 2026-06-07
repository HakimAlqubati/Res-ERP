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
            // Apply invoice_status filter
            if (!empty($filters['invoice_status']) && $filters['invoice_status'] !== \App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::ALL->value) {
                if ($filters['invoice_status'] === \App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::WITH_INVOICE->value) {
                    $itQuery->whereNotNull('grn.purchase_invoice_id');
                } else {
                    $itQuery->whereNull('grn.purchase_invoice_id');
                }
            }
        });

        if (!empty($filters['product_id'])) {
            $productIds = is_array($filters['product_id']) ? $filters['product_id'] : [$filters['product_id']];
            $query->whereIn('id', $productIds);
        }

        $needsTotalsJoin = (!empty($filters['completion_status']) && $filters['completion_status'] !== \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::ALL->value) 
                           || (!empty($filters['sort_by']) && $filters['sort_by'] === 'remaining_desc');

        if ($needsTotalsJoin) {
            $inType = \App\Models\InventoryTransaction::MOVEMENT_IN;
            $outType = \App\Models\InventoryTransaction::MOVEMENT_OUT;
            $grnClass = addslashes(\App\Models\GoodsReceivedNote::class);
            
            $inQuery = \Illuminate\Support\Facades\DB::table('inventory_transactions as it')
                ->join('goods_received_notes as grn', 'it.transactionable_id', '=', 'grn.id')
                ->select('it.product_id', \Illuminate\Support\Facades\DB::raw('SUM(it.quantity * it.package_size) as total_in'))
                ->where('it.transactionable_type', \App\Models\GoodsReceivedNote::class)
                ->where('it.movement_type', $inType)
                ->whereNull('it.deleted_at')
                ->whereNull('grn.deleted_at')
                ->groupBy('it.product_id');
                
            $outQuery = \Illuminate\Support\Facades\DB::table('inventory_transactions as out_tx')
                ->join('inventory_transactions as in_tx', 'out_tx.source_transaction_id', '=', 'in_tx.id')
                ->join('goods_received_notes as grn', 'in_tx.transactionable_id', '=', 'grn.id')
                ->select('in_tx.product_id', \Illuminate\Support\Facades\DB::raw('SUM(out_tx.quantity * out_tx.package_size) as total_out'))
                ->where('in_tx.transactionable_type', \App\Models\GoodsReceivedNote::class)
                ->where('in_tx.movement_type', $inType)
                ->where('out_tx.movement_type', $outType)
                ->whereNull('out_tx.deleted_at')
                ->whereNull('in_tx.deleted_at')
                ->whereNull('grn.deleted_at')
                ->groupBy('in_tx.product_id');

            // Apply all filters (store, date, supplier, search, invoice) automatically
            $inQuery = $this->applyToRaw($inQuery, $filters);
            $outQuery = $this->applyToRaw($outQuery, $filters);

            $query->joinSub($inQuery, 'in_totals', 'in_totals.product_id', '=', 'products.id')
                  ->leftJoinSub($outQuery, 'out_totals', 'out_totals.product_id', '=', 'products.id');

            // 1. If filtering by completion status
            if (!empty($filters['completion_status']) && $filters['completion_status'] !== \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::ALL->value) {
                $operator = $filters['completion_status'] === \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::INCOMPLETE->value ? '>' : '<=';
                $query->whereRaw("in_totals.total_in {$operator} COALESCE(out_totals.total_out, 0)");
            }
            
            // To ensure select correctness with joins (avoid overlapping column names)
            $query->select('products.*');
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
        
        if (!empty($filters['invoice_status']) && $filters['invoice_status'] !== \App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::ALL->value) {
            if ($filters['invoice_status'] === \App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::WITH_INVOICE->value) {
                $query->whereNotNull('grn.purchase_invoice_id');
            } else {
                $query->whereNull('grn.purchase_invoice_id');
            }
        }
        
        return $query;
    }
}
