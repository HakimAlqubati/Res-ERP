<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseAccountingService
{
    public static function createJournalEntryFor(PurchaseInvoice $invoice): ?JournalEntry
    {
        if ($invoice->cancelled || $invoice->purchaseInvoiceDetails->isEmpty()) {
            return null;
        }
    
        if (JournalEntry::where('related_model_id', $invoice->id)
            ->where('related_model_type', PurchaseInvoice::class)
            ->exists()) {
            return null; // Prevent duplicates
        }
    
        $totalAmount = $invoice->purchaseInvoiceDetails->sum(fn($d) => $d->total_price);
        $inventoryAccountId = $invoice->store?->inventory_account_id;
        $supplierAccountId = $invoice->supplier?->account_id;
    
        if (!$inventoryAccountId || !$supplierAccountId) {
            Log::warning('Missing account_id in store or supplier for PurchaseInvoice #' . $invoice->id);
            return null;
        }
    
        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'date' => $invoice->date,
                'description' => 'فاتورة شراء #' . $invoice->id,
                'related_model_id' => $invoice->id,
                'related_model_type' => PurchaseInvoice::class,
            ]);
    
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccountId,
                'debit' => $totalAmount,
                'credit' => 0,
                'description' => 'إضافة للمخزون نتيجة شراء فاتورة #' . $invoice->id,
            ]);
    
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $supplierAccountId,
                'debit' => 0,
                'credit' => $totalAmount,
                'description' => 'التزام تجاه المورد: ' . $invoice->supplier?->name,
            ]);
    
            DB::commit();
            return $entry;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create accounting entry for PurchaseInvoice #' . $invoice->id . ': ' . $e->getMessage());
            return null;
        }
    }
    
}
