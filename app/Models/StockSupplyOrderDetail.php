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
}
