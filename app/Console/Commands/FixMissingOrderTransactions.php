<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Store;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\FifoAllocatorInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class FixMissingOrderTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-missing-order-transactions {--tenant= : Optional tenant ID to run the command under}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing inventory transactions for orders that are ready_for_delivery or delivered.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("❌ Tenant with ID {$tenantId} not found.");

                return;
            }
            $tenant->makeCurrent();
            $this->info("🏢 Tenant [{$tenant->name} (ID: {$tenant->id})] activated.");
        }

        $this->info('Starting to fix missing inventory transactions...');

        // Fetch the raw results from the query provided by the user
        // We included an EXISTS subquery to match your `HAVING COUNT(*) > 1` condition, 
        // ensuring we only process products that were repeated in the same order.
        $missingTransactions = DB::select("
            SELECT 
                o.id AS order_id,
                od.id AS order_detail_id,
                od.product_id,
                od.unit_id,
                od.available_quantity,
                o.branch_id
            FROM orders_details od
            JOIN orders o ON o.id = od.order_id
            JOIN products p ON p.id = od.product_id
            JOIN units u ON u.id = od.unit_id
            JOIN branches b ON b.id = o.branch_id
            JOIN categories c ON c.id = p.category_id
            WHERE o.status IN ('ready_for_delivery', 'delevired')
              AND od.available_quantity > 0
              AND NOT EXISTS (
                  SELECT 1
                  FROM inventory_transactions it
                  WHERE it.transactionable_id   = o.id
                    AND it.transactionable_type = 'App\\\\Models\\\\Order'
                    AND it.product_id           = od.product_id
                    AND it.movement_type        = 'in'
                    AND it.deleted_at IS NULL
              )
              AND EXISTS (
                  SELECT 1 
                  FROM orders_details od2
                  WHERE od2.order_id = od.order_id 
                    AND od2.product_id = od.product_id
                  GROUP BY od2.order_id, od2.product_id
                  HAVING COUNT(*) > 1
              )
            ORDER BY od.order_id DESC
        ");

        if (empty($missingTransactions)) {
            $this->info('No missing transactions found.');

            return;
        }

        $this->info('Found '.count($missingTransactions).' order details with missing transactions.');

        // return;
        $fifoAllocator = app(FifoAllocatorInterface::class);
        $defaultStoreId = Store::defaultStore()?->id ?? 1;

        // Group by order to process them per order
        $groupedByOrder = collect($missingTransactions)->groupBy('order_id');

        foreach ($groupedByOrder as $orderId => $details) {
            $order = Order::with(['orderDetails.product.category', 'branch'])->find($orderId);

            if (! $order) {
                continue;
            }

            $branch = $order->branch_id
                ? Branch::withoutGlobalScopes()->with(['store' => fn ($q) => $q->withoutGlobalScopes()])->find($order->branch_id)
                : null;
            $branchStore = $branch?->store;
            $hasBranchStore = (bool) ($branchStore && $branchStore->active);

            if ($branch) {
                $order->setRelation('branch', $branch);
            }

            $this->info("Processing Order #{$order->id}...");

            // Map order details to the standard format, combining quantities if the same product is repeated
            // We will process them item by item to avoid the allocateMany bug with duplicate product IDs

            foreach ($details as $missingDetail) {
                $detail = $order->orderDetails->firstWhere('id', $missingDetail->order_detail_id);
                if (! $detail) {
                    continue;
                }

                // Find store for this product
                $storeId = $defaultStoreId;
                if ($detail->product) {
                    $storeId = defaultManufacturingStore($detail->product)?->id ?? $defaultStoreId;
                }

                $items = [[
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail->unit_id,
                    'qty' => $detail->available_quantity,
                ]];

                try {
                    DB::beginTransaction();

                    $allocationsByProduct = $fifoAllocator->allocateMany($items, (int) $storeId, $order);
                    $productAllocations = $allocationsByProduct[$detail->product_id]['allocations'] ?? [];

                    if (empty($productAllocations) || (isset($allocationsByProduct[$detail->product_id]['status']) && $allocationsByProduct[$detail->product_id]['status'] === 'error')) {
                        $msg = $allocationsByProduct[$detail->product_id]['message'] ?? 'No available stock or unknown error';
                        $this->warn("Failed to allocate for Product ID {$detail->product_id} in Order {$order->id}: {$msg}");
                        DB::rollBack();

                        continue;
                    }

                    // Before moving from inventory, check if an 'out' transaction already exists for this detail to prevent duplicates
                    // Since the query checks for 'in' missing, we'll recreate both or just 'in'?
                    // Actually, if 'in' is missing, it's safer to check if 'out' is missing as well.
                    $outExists = InventoryTransaction::where('transactionable_id', $order->id)
                        ->where('transactionable_type', Order::class)
                        ->where('product_id', $detail->product_id)
                        ->where('movement_type', 'out')
                        ->exists();

                    if (! $outExists) {
                        Order::moveFromInventory($productAllocations, $detail);
                    }

                    if ($hasBranchStore) {
                        $inExists = InventoryTransaction::where('transactionable_id', $order->id)
                            ->where('transactionable_type', Order::class)
                            ->where('product_id', $detail->product_id)
                            ->where('movement_type', 'in')
                            ->exists();

                        if (! $inExists) {
                            Order::receiveIntoBranchStore($productAllocations, $detail, $branchStore->id);
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Error for Product ID {$detail->product_id} in Order {$order->id}: ".$e->getMessage());
                }
            }
        }

        $this->info('Finished fixing missing transactions.');
    }
}
