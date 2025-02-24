<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockSupplyOrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_supply_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
    ];

    public function order()
    {
        return $this->belongsTo(StockSupplyOrder::class, 'stock_supply_order_id');
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

        static::created(function ($stockSupplyDetail) {
            $order = $stockSupplyDetail->order;
            $notes = 'Stock supply with ID ' . $stockSupplyDetail->stock_supply_order_id;
            if (isset($stockSupplyDetail->order->store_id)) {
                $notes .= ' in (' . $stockSupplyDetail->order->store->name . ')';
            }


            // Check if the order was created by a StockAdjustment
            if ($order->created_using_model_type === StockAdjustmentDetail::class) {
                $notes .= ' (Created due to Stock Adjustment ID: ' . $order->created_using_model_id . ')';
            }

            // Subtract from inventory transactions
            \App\Models\InventoryTransaction::create([
                'product_id' => $stockSupplyDetail->product_id,
                'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_IN,
                'quantity' =>  $stockSupplyDetail->quantity,
                'unit_id' => $stockSupplyDetail->unit_id,
                'movement_date' => $stockSupplyDetail->order->date ?? now(),
                'package_size' => $stockSupplyDetail->package_size,
                'store_id' => $stockSupplyDetail->order?->store_id,
                'transaction_date' => $stockSupplyDetail->order->date ?? now(),
                'notes' => $notes,
                'transactionable_id' => $stockSupplyDetail->stock_supply_order_id,
                'transactionable_type' => StockSupplyOrder::class,
            ]);
        });
    }
}
