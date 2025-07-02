<?php

namespace App\Traits\Inventory;

use App\Models\GoodsReceivedNote;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;

trait CanCancelGoodsReceivedNote
{
    public function cancelGoodsReceivedNote(GoodsReceivedNote $grn, string $reason): array
    {

        if ($grn->has_outbound_transactions) {
            return [
                'status' => false,
                'message' => 'Cannot cancel grn, outbound transactions exist.',
            ];
        }
        if ($grn->status === GoodsReceivedNote::STATUS_CANCELLED) {
            return [
                'status' => false,
                'message' => 'The GRN has already been cancelled.',
            ];
        }

        // ✅ التحقق من وجود صرف بناءً على هذه الحركات
        $inboundTransactionIds = InventoryTransaction::where('transactionable_type', GoodsReceivedNote::class)
            ->where('transactionable_id', $grn->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->pluck('id');

        $hasOutbound = InventoryTransaction::whereIn('source_transaction_id', $inboundTransactionIds)
            ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
            ->exists();

        if ($hasOutbound) {
            return [
                'status' => false,
                'message' => 'Cannot cancel the GRN: outbound transactions exist.',
            ];
        }

        if (empty($reason)) {
            return [
                'status' => false,
                'message' => 'Cancellation reason is required.',
            ];
        }

        // ✅ حذف الحركات
        InventoryTransaction::where('transactionable_type', GoodsReceivedNote::class)
            ->where('transactionable_id', $grn->id)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->delete();

        // ✅ تحديث الحالة إلى ملغي
        $grn->update([
            'status' => GoodsReceivedNote::STATUS_CANCELLED,
            'cancel_reason' => $reason,
            'cancelled_by' => Auth::id(),
        ]);

        return [
            'status' => true,
            'message' => 'GRN cancelled successfully.',
        ];
    }
}