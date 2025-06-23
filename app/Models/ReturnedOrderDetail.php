<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnedOrderDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'returned_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'price',
        'package_size',
        'notes',
    ];

    public function returnedOrder()
    {
        return $this->belongsTo(ReturnedOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->quantity * $this->price;
    }

    public function getPriceWithCurrencyAttribute(): string
    {
        return formatMoney($this->price); // assuming formatMoney() is a global helper
    }

    public function getTotalPriceWithCurrencyAttribute(): string
    {
        return formatMoney($this->getTotalPriceAttribute());
    }
    public function returnedOrderStore()
    {
        return $this->returnedOrder?->store;
    }
}
