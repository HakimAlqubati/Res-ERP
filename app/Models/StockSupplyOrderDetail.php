<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class StockSupplyOrderDetail extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'stock_supply_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
    ];
    protected $auditInclude = [
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

        static::creating(function ($stockSupplyDetail) {
            $stockSupplyDetail->price = $stockSupplyDetail->product->unitPrices()->where('unit_id', $stockSupplyDetail->unit_id)->first()->price ?? 1;
        });
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
                'price' => $stockSupplyDetail->price,
                'transaction_date' => $stockSupplyDetail->order->date ?? now(),
                'notes' => $notes,
                'transactionable_id' => $stockSupplyDetail->stock_supply_order_id,
                'transactionable_type' => StockSupplyOrder::class,
            ]);

            // 👇 إضافة الهدر المتوقع مباشرة بعد الإدخال
            $product = $stockSupplyDetail->product;
            $wastePercentage = $product->waste_stock_percentage ?? 0;

            if ($wastePercentage > 0) {
                $wasteQuantity = round(($stockSupplyDetail->quantity * $wastePercentage) / 100, 2);

                if ($wasteQuantity > 0) {
                    \App\Models\InventoryTransaction::create([
                        'product_id' => $stockSupplyDetail->product_id,
                        'movement_type' => \App\Models\InventoryTransaction::MOVEMENT_OUT,
                        'quantity' => $wasteQuantity,
                        'unit_id' => $stockSupplyDetail->unit_id,
                        'movement_date' => $stockSupplyDetail->order->date ?? now(),
                        'package_size' => $stockSupplyDetail->package_size,
                        'store_id' => $stockSupplyDetail->order?->store_id,
                        'price' => $stockSupplyDetail->price,
                        'transaction_date' => $stockSupplyDetail->order->date ?? now(),
                        'notes' => 'Auto waste recorded during supply (based on waste percentage: ' . $wastePercentage . '%)',
                        'transactionable_id' => 0,
                        'transactionable_type' => 'Waste', // رمزي فقط إذا ما عندك جدول
                        'is_waste' => true, // إذا كنت أضفت هذا الحقل في المايجريشن
                    ]);
                }
            }
        });
    }

    public function toArray()
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit->name,
            'quantity' => $this->quantity,
            'package_size' => $this->unitPrice->package_size ?? null,
        ];
    }
}
