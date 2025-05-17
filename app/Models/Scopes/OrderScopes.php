<?php

namespace App\Models\Scopes;

use App\Models\Order;

trait OrderScopes
{

    /**
     * Scope a query to only include normal orders.
     */
    public function scopeNormal($query)
    {
        return $query->where('type', Order::TYPE_NORMAL);
    }

    /**
     * Scope a query to only include manufacturing orders.
     */
    public function scopeManufacturing($query)
    {
        return $query->where('type', Order::TYPE_MANUFACTURING);
    }

    public function scopeHasManufacturingProducts($query)
    {
        return $query->whereHas('orderDetails', function ($q) {
            $q->whereHas('product.category', function ($q2) {
                $q2->where('is_manafacturing', true);
            });
        });
    }


    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeReadyForDelivery($query)
    {
        return $query->where('status', Order::READY_FOR_DELEVIRY);
    }

    public function scopeInTransfer($query)
    {
        return $query->select('orders.*')
            ->join('orders_details', 'orders_details.order_id', '=', 'orders.id')
            ->where('orders_details.available_in_store', 1)->distinct();
    }
}
