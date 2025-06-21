<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InboundOutflowReportService
{
    /**
     * يُنتج تقريرًا تفصيليًا عن كل الحركات OUT المرتبطة بحركات IN
     * بناءً على رقم فاتورة التوريد أو GRN أو stock adjustment.
     *
     * @param int $transactionableId
     * @param string|null $transactionableType
     * @return array
     */
    public function generate(int $transactionableId, ?string $transactionableType = null): array
    {
        $query = InventoryTransaction::query()
            ->with(['product', 'unit']) // تحميل اسم المنتج والوحدة
            ->where('transactionable_id', $transactionableId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNull('deleted_at');

        if ($transactionableType) {
            $query->where('transactionable_type', $transactionableType);
        }

        $inboundTransactions = $query->orderBy('transaction_date')->get();

        $report = [];

        foreach ($inboundTransactions as $inTxn) {
            $outflows = InventoryTransaction::query()
                ->with('unit') // تحميل الوحدة للحركات OUT
                ->where('source_transaction_id', $inTxn->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->whereNull('deleted_at')
                ->orderBy('transaction_date')
                ->get();

            $outDetails = [];

            foreach ($outflows as $outTxn) {
                $outDetails[] = [
                    'transaction_id' => $outTxn->id,
                    'transaction_date' => date('Y-m-d', strtotime($outTxn->transaction_date)),
                    'quantity' => formatQunantity($outTxn->quantity, 2),
                    'package_size' => $outTxn->package_size,
                    'unit' => optional($outTxn->unit)->name,
                    'price' => formatMoneyWithCurrency($outTxn->price),
                    'total_value' => round($outTxn->quantity * $outTxn->price, 2),
                    'transactionable_type' => $outTxn->transactionable_type,
                    'transactionable_id' => $outTxn->transactionable_id,
                ];
            }

            $report[] = [
                'in_transaction_id' => $inTxn->id,
                'transactionable_type' => $inTxn->transactionable_type,
                'transactionable_id' => $inTxn->transactionable_id,
                'transaction_date' => date('Y-m-d', strtotime($inTxn->transaction_date)),
                'store_id' => $inTxn->store_id,
                'product_id' => $inTxn->product_id,
                'product_name' => optional($inTxn->product)->name,
                'quantity' => formatQunantity($inTxn->quantity),
                'package_size' => $inTxn->package_size,
                'unit_id' => $inTxn->unit_id,
                'unit_name' => optional($inTxn->unit)->name,
                'price' => formatMoneyWithCurrency($inTxn->price),
                'total_value' => formatMoneyWithCurrency($inTxn->quantity * $inTxn->price),
                'outflows' => $outDetails,
            ];
        }

        return $report;
    }
}
