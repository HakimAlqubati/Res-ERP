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
            // If it had generated inventory transactions, remove them
            InventoryTransaction::where('transactionable_type', PurchaseReturn::class)
                ->where('transactionable_id', $purchaseReturn->id)
                ->delete();

            // If it had generated financial transactions, remove them
            FinancialTransaction::where('reference_type', PurchaseReturn::class)
                ->where('reference_id', $purchaseReturn->id)
                ->delete();

            $purchaseReturn->update([
                'status'        => PurchaseReturn::STATUS_CANCELLED,
                'cancelled'     => true,
                'cancel_reason' => $reason,
                'cancelled_by'  => $cancellerId,
                'cancelled_at'  => now(),
            ]);

            return $purchaseReturn->fresh(['details', 'supplier', 'store']);
        });
    }
}
