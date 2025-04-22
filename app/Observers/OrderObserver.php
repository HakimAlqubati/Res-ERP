<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\User;
use App\Services\Accounting\OrderAccountingService;
use Filament\Notifications\Notification;

class OrderObserver
{
    public function created(Order $order)
    {
        // $recipients = getAdminsToNotify();
        
        // foreach ($recipients as $recipient) {
        //     Notification::make()
        //         ->title(__('lang.order_created_notification') . $order->id)
        //         ->sendToDatabase($recipient)
        //         ->broadcast($recipient);
        // }
    }
    public function updated(Order $order)
    {
        if (
            $order->isDirty('status') &&
            $order->status === Order::READY_FOR_DELEVIRY &&
            $order->getOriginal('status') !== Order::READY_FOR_DELEVIRY
        ) {
            // ⬅️ استدعاء خدمة إنشاء القيد المحاسبي
            OrderAccountingService::createJournalEntryForDeliveredOrder($order);
        }
    }
}
