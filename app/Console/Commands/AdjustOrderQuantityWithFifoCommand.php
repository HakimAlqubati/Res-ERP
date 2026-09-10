<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\FinancialTransaction;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderLog;
use App\Models\Store;
use App\Modules\Stock\Reports\FifoBatchReports\Contracts\FifoAllocatorInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdjustOrderQuantityWithFifoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:adjust-quantity-fifo 
                            {orderId : The ID of the order}
                            {productId : The ID of the product to adjust}
                            {newQty : The new target quantity (e.g. 2.5)}
                            {--force : Force update without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adjust order item quantity and allocate the difference using FIFO';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orderId   = (int) $this->argument('orderId');
        $productId = (int) $this->argument('productId');
        $newQty    = (float) $this->argument('newQty');
        $force     = (bool) $this->option('force');

        $this->info("🔍 Checking Order #{$orderId} and Product #{$productId}...");

        $order = Order::withoutGlobalScopes()->with([
            'branch' => fn ($q) => $q->withoutGlobalScopes(),
            'branch.store' => fn ($q) => $q->withoutGlobalScopes(),
        ])->find($orderId);

        if (! $order) {
            $this->error("❌ Order #{$orderId} not found.");
            return 1;
        }

        $detail = OrderDetails::where('order_id', $orderId)
            ->where('product_id', $productId)
            ->with(['product', 'unit'])
            ->first();

        if (! $detail) {
            $this->error("❌ Product #{$productId} not found in Order #{$orderId} details.");
            return 1;
        }

        $detail->setRelation('order', $order);

        $oldQty = (float) $detail->available_quantity;
        $diffQty = $newQty - $oldQty;

        $productName = $detail->product?->name ?? "Product #{$productId}";
        $unitName    = $detail->unit?->name ?? 'Unit';

        $this->table(
            ['Field', 'Current Value', 'New Target Value', 'Difference to Allocate'],
            [
                ['Order ID', $orderId, $orderId, '-'],
                ['Product', $productName, $productName, '-'],
                ['Unit', $unitName, $unitName, '-'],
                ['Available Quantity', $oldQty, $newQty, ($diffQty > 0 ? "+{$diffQty}" : (string) $diffQty)],
                ['Order Status', $order->status, $order->status, '-'],
            ]
        );

        if (abs($diffQty) < 0.0001) {
            $this->warn("⚠️ Quantity is already {$oldQty}. No changes needed.");
            return 0;
        }

        if ($diffQty < 0) {
            $this->error("❌ New quantity ({$newQty}) is less than current quantity ({$oldQty}).");
            $this->line("   This command is designed for additional allocations (+diff) using FIFO.");
            $this->line("   For reducing quantities / returns, please use a Return Order or Stock Adjustment.");
            return 1;
        }

        if (! $force && ! $this->confirm("Are you sure you want to allocate +{$diffQty} {$unitName} via FIFO and update Order #{$orderId}?", true)) {
            $this->warn("Operation cancelled by user.");
            return 0;
        }

        $defaultStoreId = Store::defaultStore()?->id ?? 1;
        $sourceStoreId = (int) (defaultManufacturingStore($detail->product)?->id ?? $defaultStoreId);

        $branchStore = $order->branch?->store;
        $hasBranchStore = (bool) ($branchStore && $branchStore->active);

        $this->info("📦 Allocating {$diffQty} {$unitName} from Store #{$sourceStoreId} via FIFO...");

        try {
            DB::beginTransaction();

            /** @var FifoAllocatorInterface $fifoAllocator */
            $fifoAllocator = app(FifoAllocatorInterface::class);

            $allocations = $fifoAllocator->allocate(
                $detail->product_id,
                $detail->unit_id,
                $diffQty,
                $sourceStoreId,
                $order
            );

            if (empty($allocations)) {
                throw new Exception("FIFO allocator returned no allocations for Product #{$productId}.");
            }

            $this->info("   Found " . count($allocations) . " batch allocation(s):");
            foreach ($allocations as $alloc) {
                $this->line("   -> Batch #{$alloc['transaction_id']}: Deducting {$alloc['deducted_qty']} at price " . formatMoneyWithCurrency($alloc['price_based_on_unit']));
            }

            // 1. حركة خروج (OUT) من المخزن المصدر
            Order::moveFromInventory($allocations, $detail);
            $this->info("✅ Created OUT inventory transaction(s) from Store #{$sourceStoreId}.");

            // 2. حركة دخول (IN) لمخزن الفرع إذا وُجد
            if ($hasBranchStore) {
                Order::receiveIntoBranchStore($allocations, $detail, $branchStore->id);
                $this->info("✅ Created IN inventory transaction(s) into Branch Store #{$branchStore->id} ({$branchStore->name}).");
            } else {
                $this->warn("ℹ️ Branch does not have an active store. IN transaction skipped.");
            }

            // 3. تحديث تفاصيل الطلب
            $detail->update([
                'quantity'           => $newQty,
                'available_quantity' => $newQty,
                'total_unit_price'   => $newQty * $detail->price,
            ]);
            $this->info("✅ Updated OrderDetails available_quantity to {$newQty}.");

            // 4. تحديث القيد المالي للطلب إن وجد
            $finTrans = FinancialTransaction::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->first();

            if ($finTrans) {
                $newTotalCost = InventoryTransaction::where('transactionable_type', Order::class)
                    ->where('transactionable_id', $order->id)
                    ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('quantity * price'));

                if ($newTotalCost > 0) {
                    $finTrans->update(['amount' => $newTotalCost]);
                    $this->info("✅ Updated FinancialTransaction amount to " . formatMoneyWithCurrency($newTotalCost));
                }
            }

            // 5. تسجيل في سجل الطلب (OrderLog)
            OrderLog::create([
                'order_id'   => $order->id,
                'created_by' => auth()->id() ?? 1,
                'log_type'   => OrderLog::TYPE_UPDATED,
                'message'    => "Quantity for {$productName} adjusted from {$oldQty} to {$newQty} (+{$diffQty} allocated via FIFO)",
                'new_status' => $order->status,
            ]);

            DB::commit();

            $this->info("🎉 Order #{$orderId} quantity successfully adjusted to {$newQty} {$unitName} using FIFO!");
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error("❌ Error adjusting order quantity: " . $e->getMessage());
            return 1;
        }
    }
}
