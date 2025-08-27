<?php

namespace App\Traits\Inventory;

use App\Models\GoodsReceivedNote;
use App\Models\PurchaseInvoice;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;

trait CanCancelPurchaseInvoice
{
    public function cancelPurchaseInvoice(PurchaseInvoice $invoice, string $reason): array
    {
        if ($invoice->cancelled) {
            return [
                'status' => false,
                'message' => 'The invoice has already been cancelled.',
            ];
        }

        if ($invoice->has_outbound_transactions) {
            return [
                'status' => false,
                'message' => 'Cannot cancel the invoice: outbound transactions exist.',
            ];
        }

        if (empty($reason)) {
            return [
                'status' => false,
                'message' => 'Cancellation reason is required.',
            ];
        }

        $hasDirectTransactions = InventoryTransaction::where('transactionable_type', PurchaseInvoice::class)
            ->where('transactionable_id', $invoice->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->exists();

        $grn = $invoice->grn;
        $hasGrnTransactions = false;

        if ($grn) {
            $hasGrnTransactions = InventoryTransaction::where('transactionable_type', GoodsReceivedNote::class)
                ->where('transactionable_id', $grn->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->exists();
        }

        if (! $hasDirectTransactions && ! $hasGrnTransactions) {
            return [
                'status' => false,
                'message' => $grn
                    ? "Cannot cancel the invoice: it has no inventory transactions. However, it may be linked to GRN #{$grn->id}."
                    : 'Cannot cancel the invoice: it has no inventory transactions.',
            ];
        }

        // حذف الحركات الواردة
        InventoryTransaction::where('transactionable_type', PurchaseInvoice::class)
            ->where('transactionable_id', $invoice->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->delete();

        // تحديث حالة الإلغاء
        $updated = $invoice->update([
            'cancelled' => true,
            'cancel_reason' => $reason,
            'cancelled_by' => Auth::id(),
        ]);

        if (! $updated) {
            return [
                'status' => false,
                'message' => 'Invoice cancellation failed.',
            ];
        }

        return [
            'status' => true,
            'message' => 'Invoice cancelled successfully.',
        ];
    }
}