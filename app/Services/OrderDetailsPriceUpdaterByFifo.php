<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class OrderDetailsPriceUpdaterByFifo
{
    public static function updateAll()
    {
        $orders = Order::with('orderDetails')->get();
        $updated = [];

        foreach ($orders as $order) {
            foreach ($order->orderDetails as $detail) {
                $transaction = InventoryTransaction::where('transactionable_type', Order::class)
                    ->where('transactionable_id', $order->id)
                    ->where('product_id', $detail->product_id)
                    ->where('unit_id', $detail->unit_id)
                    ->latest('id')
                    ->first();

                if ($transaction && $transaction->price != $detail->price) {
                    $oldPrice = $detail->price;
                    $detail->price = $transaction->price;
                    $detail->save();

                    $updated[] = [
                        'order_id'     => $order->id,
                        'product_id'   => $detail->product_id,
                        'old_price'    => $oldPrice,
                        'new_price'    => $transaction->price,
                    ];
                }
            }
        }

        return $updated;
    }
}
