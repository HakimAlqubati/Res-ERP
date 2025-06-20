<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Services\FixFifo\FifoAllocationSaver;
use App\Services\FixFifo\FifoAllocatorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;

class AllocateAllProductsFifo extends Command
{
    protected $signature = 'fifo:allocate-all-products  {--tenant=}';
    // protected $signature = 'fifo:allocate-all-products {--products=* : Filter by specific product IDs}';

    protected $description = 'Apply FIFO allocation for all products in ready or delivered orders';

    public function handle()
    {
        // $tenantId = $this->option('tenant');

        // if (! $tenantId) {
        //     $this->error('❌ Please provide --tenant={id}');
        //     return;
        // }

        // $tenant = Tenant::find($tenantId);

        // Log::infO('hi - this is tenant :', [$tenant->name]);
        // if (! $tenant) {
        //     $this->error("❌ Tenant with ID {$tenantId} not found.");
        //     return;
        // }

        // $tenant->makeCurrent(); // ✅ تشغيل التينانت
        // $this->info("🏢 Tenant [{$tenant->id}] activated.");
        $this->info('🚀 Starting FIFO allocation for all products in orders...');

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
