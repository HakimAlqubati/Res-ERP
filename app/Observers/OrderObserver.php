<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\User;
use Filament\Notifications\Notification;

class OrderObserver
{
    public function created(Order $order)
    {
        $recipients = getAdminsToNotify();
        
        foreach ($recipients as $recipient) {
            Notification::make()
                ->title(__('lang.order_created_notification') . $order->id)
                ->sendToDatabase($recipient)
                ->broadcast($recipient);
        }
    }
    public function updated(Order $order)
    {
        if (in_array($order->status, [Order::DELEVIRED, Order::READY_FOR_DELEVIRY])) {
            OrderDetails::where('order_id', $order->id)->update(['available_in_store' => 1]);
        }
    }
}
