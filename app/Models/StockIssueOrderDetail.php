<?php

namespace App\Models;

use App\Services\FifoMethodService;
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
            $storeId = $order->store_id;
            $fifoService = new FifoMethodService($order);
            $allocations = $fifoService->getAllocateFifo(
                $stockIssueDetail->product_id,
                $stockIssueDetail->unit_id,
                $stockIssueDetail->quantity,
                $storeId
            );

            self::moveFromInventory($allocations, $stockIssueDetail);
            // $notes = 'Stock issue with id ' . $stockIssueDetail->stock_issue_order_id;
            // if (isset($stockIssueDetail->order->store_id)) {
            //     $notes .= ' in (' . $stockIssueDetail->order->store->name . ')';
            // }

            // // Check if the order was created by a StockAdjustment
            // if ($order->created_using_model_type === StockAdjustmentDetail::class) {
            //     $notes .= ' (Created due to Stock Adjustment ID: ' . $order->created_using_model_id . ')';
            // }

            // // Subtract from inventory transactions
            // \App\Models\InventoryTransaction::create([
            //     'product_id' => $stockIssueDetail->product_id,
            //     'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_OUT,
            //     'quantity' =>  $stockIssueDetail->quantity,
            //     'unit_id' => $stockIssueDetail->unit_id,
            //     'movement_date' => $stockIssueDetail->order->date ?? now(),
            //     'package_size' => $stockIssueDetail->package_size,
            //     'store_id' => $stockIssueDetail->order?->store_id,
            //     'transaction_date' => $stockIssueDetail->order->date ?? now(),
            //     'notes' => $notes,
            //     'transactionable_id' => $stockIssueDetail->stock_issue_order_id,
            //     'transactionable_type' => StockIssueOrder::class,
            // ]);
        });
    }

    public static function moveFromInventory($allocations, $detail)
    {
        $order = $detail->order;
        foreach ($allocations as $alloc) {
            InventoryTransaction::create([
                'product_id'           => $detail->product_id,
                'movement_type'        => InventoryTransaction::MOVEMENT_OUT,
                'quantity'             => $alloc['deducted_qty'],
                'unit_id'              => $alloc['target_unit_id'],
                'package_size'         => $alloc['target_unit_package_size'],
                'price'                => $alloc['price_based_on_unit'],
                'movement_date'        => $order->order_date ?? now(),
                'transaction_date'     => $order->order_date ?? now(),
                'store_id'             => $alloc['store_id'],
                'notes' => $alloc['notes'],

                'transactionable_id'   => $detail->stock_issue_order_id,
                'transactionable_type' => StockIssueOrder::class,
                'source_transaction_id' => $alloc['transaction_id'],

            ]);
        }
        return;
    }
}