<?php

namespace App\Jobs;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\StockIssueOrder;
use App\Services\FifoMethodService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AllocateFifoOutTransactionsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $transactions = $this->getMergedAndSortedTransactions();

        DB::beginTransaction();
        try {
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
                    $allocations = $fifoService->getAllocateFifo(
                        $detail->product_id,
                        $detail->unit_id,
                        $requestedQty,
                        $type

                    );

                    foreach ($allocations as $alloc) {
                        InventoryTransaction::create([
                            'product_id' => $detail->product_id,
                            'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                            'quantity' => $alloc['deducted_qty'],
                            'unit_id' => $alloc['target_unit_id'],
                            'package_size' => $alloc['target_unit_package_size'],
                            'price' => $alloc['price_based_on_unit'],
                            'movement_date' => $date,
                            'transaction_date' => $date,
                            'store_id' => $alloc['store_id'],
                            'notes' => $alloc['notes'],
                            'transactionable_id' => $model->id,
                            'transactionable_type' => get_class($model),
                            'source_transaction_id' => $alloc['transaction_id'],
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Full FIFO Transaction failed', [
                'error' => $e->getMessage(),
                'exception' => $e,
                'type' => $type,
                'model_id' => $model->id ?? null,
                'model_class' => get_class($model),
                'detail_product_id' => $detail->product_id ?? null,
                'detail_unit_id' => $detail->unit_id ?? null,
                'alloc' => $alloc ?? null,
            ]);
        }
    }


    protected function getMergedAndSortedTransactions(): \Illuminate\Support\Collection
    {
        $merged = collect();

        // أوامر الصرف
        $stockIssues = StockIssueOrder::with('details')->get();
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
            }])
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
