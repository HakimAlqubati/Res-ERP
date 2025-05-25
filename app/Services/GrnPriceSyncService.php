<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\PurchaseInvoiceDetail;
use Illuminate\Support\Facades\DB;

class GrnPriceSyncService
{
    public function syncPricesFromInvoice(int $grnId): void
    {
        $grn = GoodsReceivedNote::with('grnDetails')->findOrFail($grnId);
        $this->syncPricesForGrn($grn);
    }

    protected function syncPricesForGrn(GoodsReceivedNote $grn): void
    {
        $invoiceId = $grn->purchase_invoice_id;

        foreach ($grn->grnDetails as $grnDetail) {
            $matchingInvoiceDetail = PurchaseInvoiceDetail::where('purchase_invoice_id', $invoiceId)
                ->where('product_id', $grnDetail->product_id)
                ->where('unit_id', $grnDetail->unit_id)
                ->where('package_size', $grnDetail->package_size)
                ->first();

            if ($matchingInvoiceDetail) {
                $grnDetail->price = $matchingInvoiceDetail->price;
                $grnDetail->save();
            }
        }
    }

    public function syncAllGrnPrices(): void
    {
        $grns = GoodsReceivedNote::with('grnDetails')->get();

        DB::transaction(function () use ($grns) {
            foreach ($grns as $grn) {
                $this->syncPricesForGrn($grn);
            }
        });
    }
}
