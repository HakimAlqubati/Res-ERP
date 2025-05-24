<?php

namespace App\Services;

use App\Models\Order;
use App\Services\FifoMethodService;
use Illuminate\Support\Facades\DB;

class OrderInventoryFixService
{
    public function fixInventoryForOrder(Order $order): array
    {
        if (!in_array($order->status, [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])) {
            return ['status' => 'error', 'message' => 'Order status must be READY_FOR_DELEVIRY or DELEVIRED.'];
        }

        if ($order->has_inventory_impact) {
            return ['status' => 'info', 'message' => 'Inventory already processed for this order.'];
        }

        DB::beginTransaction();
        try {
            foreach ($order->orderDetails as $detail) {
                $fifoService = new FifoMethodService($order);

                $allocations = $fifoService->allocateFIFO(
                    $detail->product_id,
                    $detail->unit_id,
                    $detail->available_quantity
                );

                Order::moveFromInventory($allocations, $detail);
            }

            DB::commit();
            return ['status' => 'success', 'message' => "Inventory processed for Order #{$order->id}."];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['status' => 'error', 'message' => 'Failed to process inventory: ' . $e->getMessage()];
        }
    }

    public function previewAllocations(Order $order): array
    {
        if (!in_array($order->status, [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])) {
            return ['status' => 'error', 'message' => 'Order status must be READY_FOR_DELEVIRY or DELEVIRED.'];
        }

        if ($order->has_inventory_impact) {
            return ['status' => 'info', 'message' => 'Inventory already processed for this order.'];
        }

        $results = [];

        try {
            foreach ($order->orderDetails as $detail) {
                $fifoService = new FifoMethodService($order);

                $allocations = $fifoService->allocateFIFO(
                    $detail->product_id,
                    $detail->unit_id,
                    $detail->available_quantity
                );

                $results[] = [
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail->unit_id,
                    'requested_quantity' => $detail->available_quantity,
                    'allocations' => $allocations,
                ];
            }

            return ['status' => 'success', 'order_id' => $order->id, 'allocations_result' => $results];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to preview allocations: ' . $e->getMessage()];
        }
    }
}
