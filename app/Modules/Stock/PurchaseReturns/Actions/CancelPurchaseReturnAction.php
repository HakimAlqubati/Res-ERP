<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Actions;

use App\Models\FinancialTransaction;
use App\Models\InventoryTransaction;
use App\Models\PurchaseReturn;
use App\Modules\Stock\PurchaseReturns\Exceptions\PurchaseReturnValidationException;
use Illuminate\Support\Facades\DB;

final class CancelPurchaseReturnAction
{
    public function execute(PurchaseReturn $purchaseReturn, string $reason, int $cancellerId): PurchaseReturn
    {
        if ($purchaseReturn->cancelled) {
            throw new PurchaseReturnValidationException('This purchase return is already cancelled.');
        }

        if (empty(trim($reason))) {
            throw new PurchaseReturnValidationException('Cancellation reason is required.');
        }

        return DB::transaction(function () use ($purchaseReturn, $reason, $cancellerId) {
            // Retrieve inventory transactions before deletion to know affected products/stores
            $inventoryTxs = InventoryTransaction::where('transactionable_type', PurchaseReturn::class)
                ->where('transactionable_id', $purchaseReturn->id)
                ->get();

            $affectedProducts = $inventoryTxs->map(fn($tx) => [
                'product_id' => (int) $tx->product_id,
                'store_id'   => (int) $tx->store_id,
            ])->unique(fn($item) => $item['product_id'] . '_' . $item['store_id'])->values();

            // Delete individual transactions to trigger Eloquent events
            $inventoryTxs->each->delete();

            // If it had generated financial transactions, remove them via model
            FinancialTransaction::where('reference_type', PurchaseReturn::class)
                ->where('reference_id', $purchaseReturn->id)
                ->get()
                ->each->delete();

            $purchaseReturn->update([
                'status'        => PurchaseReturn::STATUS_CANCELLED,
                'cancelled'     => true,
                'cancel_reason' => $reason,
                'cancelled_by'  => $cancellerId,
                'cancelled_at'  => now(),
            ]);

            // Re-sync batch prices for all affected products
            $tenantId = app(\Spatie\Multitenancy\Contracts\IsTenant::class)::current()?->id;
            foreach ($affectedProducts as $item) {
                try {
                    \App\Modules\Stock\Jobs\SyncProductCurrentBatchPriceJob::dispatch(
                        $item['product_id'],
                        $item['store_id'],
                        $tenantId
                    )->onConnection('tenant');
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to dispatch SyncProductCurrentBatchPriceJob on return cancellation: {$e->getMessage()}");
                }
            }

            return $purchaseReturn->fresh(['details', 'supplier', 'store']);
        });
    }
}
