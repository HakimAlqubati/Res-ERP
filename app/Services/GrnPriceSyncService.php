<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\PurchaseInvoiceDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrnPriceSyncService
{
    /**
     * مزامنة أسعار GRN واحد من الفاتورة المرتبطة به.
     */
    public function syncPricesFromInvoice(int $grnId): void
    {
        $grn = GoodsReceivedNote::with('grnDetails')->findOrFail($grnId);
        $this->syncPricesForGrn($grn);
    }

    /**
     * مزامنة أسعار GRN معين إذا كان مرتبطًا بفاتورة شراء.
     */
    protected function syncPricesForGrn(GoodsReceivedNote $grn): void
    {
        $invoiceId = $grn->purchase_invoice_id;

        if (!$invoiceId) {
            return; // لا يوجد فاتورة مرتبطة، تجاهل GRN هذا
        }

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

    /**
     * مزامنة أسعار جميع GRNs المرتبطة بفواتير شراء.
     */
    public function syncAllGrnPrices(): void
    {
        $grns = GoodsReceivedNote::whereNotNull('purchase_invoice_id')
            ->with('grnDetails')
            ->get();

        DB::transaction(function () use ($grns) {
            foreach ($grns as $grn) {
                $this->syncPricesForGrn($grn);
            }
        });

        Log::info("✅ Synced GRN prices from purchase invoices. Total GRNs processed: " . $grns->count());
    }
}
