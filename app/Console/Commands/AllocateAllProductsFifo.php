<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Services\FixFifo\FifoAllocationSaver;
use App\Services\FixFifo\FifoAllocatorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AllocateAllProductsFifo extends Command
{
    protected $signature = 'fifo:allocate-all-products';

    protected $description = 'Apply FIFO allocation for all products in ready or delivered orders';

    public function handle()
    {
        $this->info('🚀 Starting FIFO allocation for all products in orders...');

        $productIds = DB::table('orders_details as od')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('o.deleted_at')
            ->distinct()
            ->pluck('od.product_id');

        $fifoService = new FifoAllocatorService();

        foreach ($productIds as $productId) {
            $allocations = $fifoService->allocate($productId);
            $this->line("⚙️ Allocating for product_id: {$productId}");

            try {
                FifoAllocationSaver::save($allocations, $productId);

                $this->info("✅ Allocation completed for product_id: {$productId}");
            } catch (\Throwable $e) {
                Log::error("❌ Error allocating product_id={$productId}", [
                    'error' => $e->getMessage(),
                ]);

                $this->error("❌ Failed for product_id: {$productId} - " . $e->getMessage());
            }
        }

        $this->info('🎉 FIFO allocation completed for all products.');
    }
}
