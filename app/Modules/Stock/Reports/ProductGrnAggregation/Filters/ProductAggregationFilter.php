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

        if (!empty($filters['completion_status']) && $filters['completion_status'] !== \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::ALL->value) {
            $inType = \App\Models\InventoryTransaction::MOVEMENT_IN;
            $outType = \App\Models\InventoryTransaction::MOVEMENT_OUT;
            $grnClass = \App\Models\GoodsReceivedNote::class;
            
            $operator = $filters['completion_status'] === \App\Modules\Stock\Reports\Enums\FilterCompletionStatus::INCOMPLETE->value ? '>' : '<=';
            
            $invoiceConditionIn = "";
            $invoiceConditionOut = "";
            
            if (!empty($filters['invoice_status']) && $filters['invoice_status'] !== \App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::ALL->value) {
                if ($filters['invoice_status'] === \App\Modules\Stock\Reports\Enums\FilterInvoiceLinkStatus::WITH_INVOICE->value) {
                    $invoiceConditionIn = " AND grn.purchase_invoice_id IS NOT NULL";
                    $invoiceConditionOut = " AND grn.purchase_invoice_id IS NOT NULL";
                } else {
                    $invoiceConditionIn = " AND grn.purchase_invoice_id IS NULL";
                    $invoiceConditionOut = " AND grn.purchase_invoice_id IS NULL";
                }
            }
            
            $query->whereRaw("(
                SELECT COALESCE(SUM(it.quantity * it.package_size), 0)
                FROM inventory_transactions it
                INNER JOIN goods_received_notes grn ON it.transactionable_id = grn.id
                WHERE it.product_id = products.id
                AND it.transactionable_type = '{$grnClass}'
                AND it.movement_type = '{$inType}'
                AND it.deleted_at IS NULL
                AND grn.deleted_at IS NULL
                {$invoiceConditionIn}
            ) {$operator} (
                SELECT COALESCE(SUM(out_tx.quantity * out_tx.package_size), 0)
                FROM inventory_transactions out_tx
                INNER JOIN inventory_transactions in_tx ON out_tx.source_transaction_id = in_tx.id
                INNER JOIN goods_received_notes grn ON in_tx.transactionable_id = grn.id
                WHERE in_tx.product_id = products.id
                AND in_tx.transactionable_type = '{$grnClass}'
                AND in_tx.movement_type = '{$inType}'
                AND out_tx.movement_type = '{$outType}'
                AND out_tx.deleted_at IS NULL
                AND in_tx.deleted_at IS NULL
                AND grn.deleted_at IS NULL
                {$invoiceConditionOut}
            )");
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
