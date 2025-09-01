<?php

namespace App\Observers;

use App\Models\OrderDetails;

class OrderDetailsObserver
{
    /**
     * Handle the OrderDetails "created" event.
     *
     * @param OrderDetails $orderDetails
     * @return void
     */
    public function created(OrderDetails $orderDetails)
    {

    }

    /**
     * Handle the OrderDetails "updated" event.
     *
     * @param OrderDetails $orderDetails
     * @return void
     */
    public function updated(OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Handle the OrderDetails "deleted" event.
     *
     * @param OrderDetails $orderDetails
     * @return void
     */
    public function deleted(OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Handle the OrderDetails "restored" event.
     *
     * @param OrderDetails $orderDetails
     * @return void
     */
    public function restored(OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Handle the OrderDetails "force deleted" event.
     *
     * @param OrderDetails $orderDetails
     * @return void
     */
    public function forceDeleted(OrderDetails $orderDetails)
    {
        //
    }
}
