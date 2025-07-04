<?php

namespace App\Observers;

use App\Models\StockTransferOrder;
use Illuminate\Support\Facades\Log;

class StockTransferOrderObserver
{
    /**
     * Handle the StockTransferOrder "created" event.
     */
    public function created(StockTransferOrder $order): void {}

    /**
     * Handle the StockTransferOrder "updated" event.
     */
    public function updated(StockTransferOrder $order): void
    {
        Log::info('in_observer', [$order]);
        // الحالة تغيرت من شيء آخر إلى approved؟
        if (
            $order->isDirty('status') &&
            $order->status === StockTransferOrder::STATUS_APPROVED &&
            $order->getOriginal('status') !== StockTransferOrder::STATUS_APPROVED
        ) {
            $order->loadMissing('details');
            $order->createInventoryTransactionsFromTransfer();
        }
    }

    /**
     * Handle the StockTransferOrder "deleted" event.
     */
    public function deleted(StockTransferOrder $stockTransferOrder): void
    {
        //
    }

    /**
     * Handle the StockTransferOrder "restored" event.
     */
    public function restored(StockTransferOrder $stockTransferOrder): void
    {
        //
    }

    /**
     * Handle the StockTransferOrder "force deleted" event.
     */
    public function forceDeleted(StockTransferOrder $stockTransferOrder): void
    {
        //
    }
}