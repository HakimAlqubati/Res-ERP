<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class StockIssueOrderDetail extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'stock_issue_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'package_size',
    ];
    protected $auditInclude = [
        'stock_issue_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'package_size',
    ];

    public function order()
    {
        return $this->belongsTo(StockIssueOrder::class, 'stock_issue_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($stockIssueDetail) {
            $order = $stockIssueDetail->order;
            $notes = 'Stock issue with id ' . $stockIssueDetail->stock_issue_order_id;
            if (isset($stockIssueDetail->order->store_id)) {
                $notes .= ' in (' . $stockIssueDetail->order->store->name . ')';
            }

            // Check if the order was created by a StockAdjustment
            if ($order->created_using_model_type === StockAdjustmentDetail::class) {
                $notes .= ' (Created due to Stock Adjustment ID: ' . $order->created_using_model_id . ')';
            }

            // ⚠️ استخدام FIFO بدلاً من خصم كل الكمية دفعة واحدة
            $fifoService = new \App\Services\FifoMethodService($order);
            $allocations = $fifoService->getAllocateFifo(
                $stockIssueDetail->product_id,
                $stockIssueDetail->unit_id,
                $stockIssueDetail->quantity
            );

            foreach ($allocations as $alloc) {
                \App\Models\InventoryTransaction::create([
                    'product_id' => $stockIssueDetail->product_id,
                    'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                    'quantity' =>  $alloc['deducted_qty'],
                    'unit_id' => $alloc['target_unit_id'],
                    'movement_date' => $order->date ?? now(),
                    'package_size' => $alloc['target_unit_package_size'],
                    'store_id' => $alloc['store_id'],
                    'transaction_date' => $order->date ?? now(),
                    'notes' => $notes,
                    'transactionable_id' => $stockIssueDetail->stock_issue_order_id,
                    'transactionable_type' => \App\Models\StockIssueOrder::class,
                    'source_transaction_id' => $alloc['transaction_id'],
                    'price' => $alloc['price_based_on_unit'],
                ]);
            }
        });
    }
}
