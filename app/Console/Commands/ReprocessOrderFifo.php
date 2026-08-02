<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\FifoMethodService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReprocessOrderFifo extends Command
{
    protected $signature = 'order:reprocess-fifo {orderId}';
    protected $description = 'Reprocess FIFO inventory transactions for a specific order (use when FIFO was skipped)';

    public function handle()
    {
        $orderId = $this->argument('orderId');
        $order = Order::with('orderDetails', 'branch.store')->find($orderId);

        if (!$order) {
            $this->error("Order #{$orderId} not found.");
            return 1;
        }

        if ($order->status !== Order::READY_FOR_DELEVIRY) {
            $this->error("Order #{$orderId} status is '{$order->status}', expected 'ready_for_delivery'.");
            return 1;
        }

        // Check if inventory transactions already exist for this order
        $existingCount = \App\Models\InventoryTransaction::where('transactionable_type', Order::class)
            ->where('transactionable_id', $orderId)
            ->count();

        if ($existingCount > 0) {
            $this->warn("Order #{$orderId} already has {$existingCount} inventory transactions. Skipping.");
            return 0;
        }

        $this->info("Processing FIFO for Order #{$orderId} with {$order->orderDetails->count()} items...");

        try {
            DB::beginTransaction();

            foreach ($order->orderDetails as $detail) {
                try {
                    $fifoService = new FifoMethodService($order);

                    $allocations = $fifoService->getAllocateFifo(
                        $detail->product_id,
                        $detail->unit_id,
                        $detail->available_quantity
                    );

                    Order::moveFromInventory($allocations, $detail);

                    if ($order->branch && $order->branch->store && $order->branch->store->active) {
                        Order::receiveIntoBranchStore($allocations, $detail);
                    }

                    $this->info("  ✅ Product #{$detail->product_id} - {$detail->available_quantity} units allocated");
                } catch (\Exception $e) {
                    $this->warn("  ⚠️ Product #{$detail->product_id} SKIPPED: " . $e->getMessage());
                }
            }

            // Financial sync
            if ($order->branch && $order->branch->type !== \App\Models\Branch::TYPE_RESELLER) {
                app(\App\Services\Financial\TransferFinancialSyncService::class)->syncOrder($order);
                $this->info("  ✅ Financial transaction synced");
            }

            DB::commit();
            $this->info("✅ Order #{$orderId} FIFO reprocessed successfully!");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Failed: " . $e->getMessage());
            return 1;
        }
    }
}
