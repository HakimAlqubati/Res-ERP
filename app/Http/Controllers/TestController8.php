<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StockIssueOrder;
use App\Services\FifoMethodService;
use Illuminate\Http\Request;

class TestController8 extends Controller
{


    public function testJobAllocationOut(Request $request)
    {
        $transactions = $this->getMergedAndSortedTransactions();
        $allocations = [];
        foreach ($transactions as $trx) {
            $model = $trx['model'];
            $details = $trx['details'];
            $date = $trx['date'];
            $type = $trx['type'];

            foreach ($details as $detail) {
                $fifoService = new FifoMethodService($model);

                $requestedQty = get_class($model) == Order::class
                    ? $detail->available_quantity
                    : $detail->quantity;
                $allocations[] = $fifoService->getAllocateFifo(
                    $detail->product_id,
                    $detail->unit_id,
                    $requestedQty,
                    $type

                );
            }
            dd($allocations);
        }
        return $allocations;
    }
    protected function getMergedAndSortedTransactions(): \Illuminate\Support\Collection
    {
        $merged = collect();

        // أوامر الصرف
        $stockIssues = StockIssueOrder::with('details')->limit(2)->get();
        foreach ($stockIssues as $issue) {
            $merged->push([
                'type' => 'stock_issue',
                'model' => $issue,
                'details' => $issue->details,
                'date' => $issue->order_date,
            ]);
        }

        // الطلبات
        $orders = Order::whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])

            ->with(['orderDetails', 'logs' => function ($q) {
                $q->where('log_type', 'change_status')
                    ->where('new_status', Order::READY_FOR_DELEVIRY);
            }])->limit(20)
            ->get();

        foreach ($orders as $order) {
            $log = $order->logs->sortByDesc('created_at')->first();


            if (!$log) {
                continue; // تخطي إذا لم يكن هناك log
            }

            $merged->push([
                'type' => 'order',
                'model' => $order,
                'details' => $order->orderDetails,
                'date' => $log->created_at,
            ]);
        }

        return $merged->sortBy('date')->values();
    }
}
