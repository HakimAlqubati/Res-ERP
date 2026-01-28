<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController9 extends Controller
{
    /**
     * Test Race Condition - Simulates 2 concurrent updates
     * 
     * Usage: GET /api/test-race/{orderId}
     */
    public function testRaceCondition($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Step 1: Reset to processing
        DB::table('orders')->where('id', $orderId)->update(['status' => 'processing']);

        // Step 2: Count logs before
        $logsBefore = OrderLog::where('order_id', $orderId)
            ->where('log_type', 'change_status')
            ->where('new_status', 'ready_for_delivery')
            ->count();
            

        // Step 3: Load order TWICE before saving (simulates race condition)
        $order1 = Order::find($orderId);
        $order2 = Order::find($orderId);

        // Both see status = 'processing'
        $original1 = $order1->getOriginal('status');
        $original2 = $order2->getOriginal('status');

         // Save both
        $order1->status = 'ready_for_delivery';
        $order1->save();
        // dd($order1);

        // dd($order2);    
        // $order2->status = 'ready_for_delivery';
        // $order2->save();

        // dd($order1,$order2);
        // Step 4: Count logs after
        $logsAfter = OrderLog::where('order_id', $orderId)
            ->where('log_type', 'change_status')
            ->where('new_status', 'ready_for_delivery')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'created_at', 'message']);

            // dd($logsAfter);
        $newLogs = $logsAfter->count() - $logsBefore;

        // dd($logsAfter,$logsBefore,$newLogs);
        return response()->json([
            'order_id' => $orderId,
            'original_status_1' => $original1,
            'original_status_2' => $original2,
            'logs_before' => $logsBefore,
            'logs_after' => $logsAfter->count(),
            'new_logs_created' => $newLogs,
            'race_condition_detected' => $newLogs > 1,
            'recent_logs' => $logsAfter->take(5),
        ]);
    }
}
