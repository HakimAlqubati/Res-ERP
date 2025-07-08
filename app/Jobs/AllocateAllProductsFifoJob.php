<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\FixFifo\FifoAllocationSaver;
use App\Services\FixFifo\FifoAllocatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AllocateAllProductsFifoJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        

        $productIds = DB::table('orders_details as od')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('o.deleted_at')
            ->distinct()
            ->pluck('od.product_id');

        $productIdsFromOrders = DB::table('orders_details as od')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('o.deleted_at')
            ->pluck('od.product_id');

        $productIdsFromIssues = DB::table('stock_issue_order_details as sid')
            ->join('stock_issue_orders as si', 'sid.stock_issue_order_id', '=', 'si.id')
            ->whereNull('si.deleted_at')
            ->pluck('sid.product_id');

        $productIdsFromAdjustments = DB::table('stock_adjustment_details')
            ->where('adjustment_type', 'decrease')
            ->pluck('product_id');

        $productIds = $productIdsFromOrders
            ->merge($productIdsFromIssues)
            ->merge($productIdsFromAdjustments)
            ->unique()
            ->sort()
            ->values();
        // $productIds = collect([1]);


        $fifoService = new FifoAllocatorService();
        // $productIds = $this->option('products');

        foreach ($productIds as $productId) {
            $allocations = $fifoService->allocate($productId);
            try {
                FifoAllocationSaver::save($allocations, $productId);
            } catch (\Throwable $e) {
                Log::error("❌ Error allocating product_id={$productId}", [
                    'error' => $e->getMessage(),
                ]);

                Log::error("❌ Error allocating product_id={$productId}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        Log::info('🎉 FIFO allocation completed for all products.');
    }
}