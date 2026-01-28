<?php

namespace App\Http\Controllers\Api\Developer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FifoMethodService;
use Illuminate\Http\JsonResponse;

/**
 * Developer Order Controller
 * اختبار الطلبيات و FIFO
 * ⚠️ للتطوير فقط
 */
class DevOrderController extends Controller
{
    /**
     * جلب تفاصيل طلبية مع FIFO allocations
     * 
     * GET /api/dev/order/{id}/details
     */
    public function details(int $id): JsonResponse
    {
        $order = Order::with(['orderDetails.product', 'orderDetails.unit'])->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $startTime = microtime(true);

        $fifoService = new FifoMethodService($order);
        $allocations = [];

        foreach ($order->orderDetails as $detail) {
            $allocations[] = [
                'detail_id' => $detail->id,
                'product_id' => $detail->product_id,
                'unit_id' => $detail->unit_id,
                'qty' => $detail->available_quantity,
                'fifo' => $fifoService->getAllocateFifo(
                    $detail->product_id,
                    $detail->unit_id,
                    $detail->available_quantity
                ),
            ];
        }

        $endTime = microtime(true);
        $timeMs = round(($endTime - $startTime) * 1000, 2);

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'details_count' => $order->orderDetails->count(),
            'allocations_count' => count($allocations),
            'time_ms' => $timeMs,
            'allocations' => $allocations,
        ]);
    }

    /**
     * تنفيذ moveFromInventory على طلبية
     * 
     * POST /api/dev/order/{id}/move-inventory
     * Body: { "limit": 5, "execute": false }
     */
    public function moveInventory(int $id): JsonResponse
    {
        $limit = request()->input('limit', 50); // افتراضي 3 تفاصيل فقط
        $execute = request()->input('execute', true); // افتراضي: لا تنفذ

        $order = Order::with(['orderDetails.product', 'orderDetails.unit'])->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $startTime = microtime(true);
        $maxTimeSeconds = 30; // أقصى وقت 30 ثانية

        $fifoService = new FifoMethodService($order);
        $results = [];
        $processed = 0;

        foreach ($order->orderDetails->take($limit) as $detail) {
            // فحص الوقت
            if ((microtime(true) - $startTime) > $maxTimeSeconds) {
                $results[] = ['warning' => 'Timeout reached, stopping early'];
                break;
            }

            $detailStart = microtime(true);

            $allocations = $fifoService->getAllocateFifo(
                $detail->product_id,
                $detail->unit_id,
                $detail->available_quantity
            );

            $fifoTime = round((microtime(true) - $detailStart) * 1000, 2);

            $moveTime = 0;
            if ($execute) {
                $moveStart = microtime(true);
                Order::moveFromInventory($allocations, $detail);
                $moveTime = round((microtime(true) - $moveStart) * 1000, 2);
            }

            $results[] = [
                'detail_id' => $detail->id,
                'product_id' => $detail->product_id,
                'allocations_count' => count($allocations),
                'fifo_time_ms' => $fifoTime,
                'move_time_ms' => $moveTime,
                'executed' => $execute,
            ];

            $processed++;
        }

        $endTime = microtime(true);
        $totalTimeMs = round(($endTime - $startTime) * 1000, 2);

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'total_details' => $order->orderDetails->count(),
            'limit_used' => $limit,
            'details_processed' => $processed,
            'execute_mode' => $execute,
            'total_time_ms' => $totalTimeMs,
            'results' => $results,
        ]);
    }
}
