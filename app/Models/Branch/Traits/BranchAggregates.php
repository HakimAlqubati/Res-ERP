<?php

namespace App\Models\Branch\Traits;

use App\Models\Order;

trait BranchAggregates
{
    /**
     * ⚠️ هذه المجاميع قد تكون مكلفة على قوائم كبيرة.
     * استعمل withCount/withSum عند اللزوم لتسريع الاستعلامات الجماعية.
     */

    public function getOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getTotalQuantityAttribute()
    {
        return $this->orders()
            ->join('orders_details', 'orders_details.order_id', '=', 'orders.id')
            ->whereIn('orders.status', [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])
            ->sum('orders_details.available_quantity');
    }

    public function getTotalSalesAttribute(): float
    {
        return (float) $this->resellerSaleItems()->where('is_cancelled', 0)->sum('total_price');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->resellerPaidAmounts()->sum('amount');
    }

    public function getResellerBalanceAttribute(): float
    {
        return (float) ($this->total_sales - $this->total_paid);
    }

    public function getTotalOrdersAmountAttribute(): float
    {
        // يفضّل أن يكون هناك accessor في Order يعيد total_amount بدقّة
        return (float) $this->orders()
            ->with('orderDetails') // تقليل N+1
            ->get()
            ->sum(fn($order) => $order->total_amount);
    }
}
