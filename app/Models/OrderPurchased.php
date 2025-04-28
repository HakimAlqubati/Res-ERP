<?php

namespace App\Models;

class OrderPurchased extends Order
{

    protected $table = 'orders';

    public function orderDetails()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }
    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'order_store', 'order_id', 'store_id');
    }
}
