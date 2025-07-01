<?php

namespace App\Traits\Inventory;

use App\Models\StockSupplyOrder;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;

trait CanCancelStockSupplyOrder
{
    public function cancelStockSupplyOrder(StockSupplyOrder $order, string $reason): array
    {
        if ($order->cancelled) {
            return [
                'status' => false,
                'message' => 'The order has already been cancelled.',
            ];
        }

        if ($order->has_outbound_transactions) {
            return [
                'status' => false,
                'message' => 'Cannot cancel the order: outbound transactions exist.',
            ];
        }

        if (empty($reason)) {
            return [
                'status' => false,
                'message' => 'Cancellation reason is required.',
            ];
        }

        InventoryTransaction::where('transactionable_type', StockSupplyOrder::class)
            ->where('transactionable_id', $order->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->delete();

        $updated = $order->update([
            'cancelled' => true,
            'cancel_reason' => $reason,
            'cancelled_by' => Auth::id(),
        ]);

        if (! $updated) {
            return [
                'status' => false,
                'message' => 'Order cancellation failed.',
            ];
        }

        return [
            'status' => true,
            'message' => 'Order cancelled successfully.',
        ];
    }
}