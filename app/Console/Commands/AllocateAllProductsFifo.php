<?php

namespace App\Console\Commands;

use Throwable;
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
        $tenantId = $this->option('tenant');

        // if (! $tenantId) {
        //     $this->error('❌ Please provide --tenant={id}');
        //     return;
        // }

        $tenant = Tenant::find($tenantId);

        if (isset($tenantId) && ! $tenant) {
            $this->error("❌ Tenant with ID {$tenantId} not found.");
        }
        if ($tenant) {
            Log::infO('hi - this is tenant :', [$tenant->name]);
            $tenant->makeCurrent(); // ✅ تشغيل التينانت

        }
        // $this->info("🏢 Tenant [{$tenant->id}] activated.");
        $this->info('🚀 Starting FIFO allocation for all products in orders...');

        // $productIds = DB::table('orders_details as od')
        //     ->join('orders as o', 'od.order_id', '=', 'o.id')
        //     ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
        //     ->whereNull('o.deleted_at')
        //     ->distinct()
        //     ->pluck('od.product_id');

        // $productIdsFromOrders = DB::table('orders_details as od')
        //     ->join('orders as o', 'od.order_id', '=', 'o.id')
        //     ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
        //     ->whereNull('o.deleted_at')
        //     ->pluck('od.product_id');

        // $productIdsFromIssues = DB::table('stock_issue_order_details as sid')
        //     ->join('stock_issue_orders as si', 'sid.stock_issue_order_id', '=', 'si.id')
        //     ->whereNull('si.deleted_at')
        //     ->pluck('sid.product_id');

        // $productIdsFromAdjustments = DB::table('stock_adjustment_details')
        //     ->where('adjustment_type', 'decrease')
        //     ->pluck('product_id');

        // $productIds = $productIdsFromOrders
        //     ->merge($productIdsFromIssues)
        //     ->merge($productIdsFromAdjustments)
        //     ->unique()
        //     ->sort()
        //     ->values();
        // $productIds = collect([1]);

        // بناء كويري واحد يحتوي كل المصادر باستخدام union
        $productIds = DB::table('orders_details as od')
            ->join('orders as o', 'od.order_id', '=', 'o.id')
            ->whereIn('o.status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereNull('o.deleted_at')
            ->select('od.product_id')

            ->union(

                DB::table('stock_issue_order_details as sid')
                    ->join('stock_issue_orders as si', 'sid.stock_issue_order_id', '=', 'si.id')
                    ->whereNull('si.deleted_at')
                    ->select('sid.product_id')
            )

            ->union(
                DB::table('stock_adjustment_details')
                    ->where('adjustment_type', 'decrease')
                    ->select('product_id')
            )
            ->union(
                DB::table('stock_transfer_order_details as std')
                    ->join('stock_transfer_orders as st', 'std.stock_transfer_order_id', '=', 'st.id')
                    ->where('st.status', 'approved')
                    ->whereNull('st.deleted_at')
                    ->select('std.product_id')
            )

            ->distinct()
            ->pluck('product_id')->unique()
            ->sort() // ترتيب تصاعدي
            ->values();

        $total = $productIds->count();
        $this->info("🚀 Found {$total} products to allocate. Dispatching background jobs...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($productIds as $productId) {

            // Delete existing allocations for this product first specific to Order context
            // Note: Ideally the Job should handle this or we do it here. 
            // The previous code chunked delete for ALL transactionable_type = Order::class
            // But now we dispatch per product.
            // Let's keep the bulk delete logic or let the Job handle it?
            // The previous code did: InventoryTransaction::where('transactionable_type', Order::class)...->forceDelete();
            // This is risky if we have other types.
            // But since 'RebuildInventory' already wiped ALL 'out' transactions, we might not need to delete again?
            // RebuildInventory deleted `where('movement_type', 'out')->forceDelete();`
            // So we are safe to just dispatch.

            \App\Jobs\AllocateProductFifoJob::dispatch($productId);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Dispatched {$total} jobs to the queue.");
        $this->info("👉 Run 'php artisan queue:work' to process them.");
    }
}
