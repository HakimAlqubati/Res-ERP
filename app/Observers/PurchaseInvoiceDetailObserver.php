<?php

namespace App\Observers;

use App\Models\ProductItem;
use App\Models\PurchaseInvoiceDetail;
use App\Models\InventoryTransaction;
use App\Services\Accounting\PurchaseAccountingService;
use App\Services\ProductCostingService;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceDetailObserver
{
    /**
     * Handle the PurchaseInvoiceDetail "created" event.
     */
    public function created(PurchaseInvoiceDetail $purchaseInvoiceDetail): void
    {
           // ✅ 2. إنشاء القيد المحاسبي
        try {
            PurchaseAccountingService::createJournalEntryFor($purchaseInvoiceDetail->purchaseInvoice);
            Log::info("✅ Created accounting journal entry for PurchaseInvoice #{$purchaseInvoiceDetail->purchase_invoice_id}");
        } catch (\Throwable $e) {
            Log::error("❌ Failed to create journal entry for PurchaseInvoice #{$purchaseInvoiceDetail->purchase_invoice_id}: {$e->getMessage()}");
        }
    }
}
