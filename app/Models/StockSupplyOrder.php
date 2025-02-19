<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockSupplyOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_date',
        'store_id',
        'notes',
        'cancelled',
        'cancel_reason',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }



    public function details()
    {
        return $this->hasMany(StockSupplyOrderDetail::class, 'stock_supply_order_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($stockSupplyOrder) {
            $stockSupplyOrder->created_by = auth()->id();
        });
    }
}
