<?php

namespace App\Services\MigrationScripts;

use App\Models\PurchaseInvoiceDetail;
use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoice;
use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;

use Exception;

class PurchaseInvoiceInventoryMigrationService
{
    /**
     * Migrate inventory transactions based on purchase invoice details.
     *
     * @return void
     */
    public static function migrateInventoryTransactions()
    {
        // Retrieve all purchase invoice details
        $purchaseDetails = PurchaseInvoiceDetail::with('purchaseInvoice.store')->get();
        foreach ($purchaseDetails as $detail) {
            self::migrateTransactionForDetail($detail);
        }
    }

    /**
     * Migrate inventory transaction for a specific purchase invoice detail.
     *
     * @param PurchaseInvoiceDetail $detail
     * @return void
     */
    public static function migrateTransactionForDetail(PurchaseInvoiceDetail $detail)
    {
        // DB::beginTransaction(); // Start database transaction

        // try {
        // Check if an inventory transaction already exists for this detail
        // $exists = InventoryTransaction::where('product_id', $detail->product_id)
        //     ->where('reference_id', $detail->purchase_invoice_id)
        //     ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
        //     ->exists();

        // if ($exists) {
        //     Log::info("Inventory transaction already exists for Purchase Invoice ID: {$detail->purchase_invoice_id}, Product ID: {$detail->product_id}");
        //     DB::rollBack(); // Rollback transaction as no change was made
        //     return;
        // }

        // Prepare transaction notes
        $notes = "Purchase invoice ID {$detail->purchase_invoice_id}";
        if (isset($detail->purchaseInvoice->store_id)) {
            $notes .= " in ({$detail->purchaseInvoice->store->name})";
        }

        if ($detail->package_size) {

            // Create inventory transaction
            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $detail->quantity,
                'package_size' => $detail->package_size,
                'price' => $detail->price,
                'movement_date' => $detail->purchaseInvoice->date ?? now(),
                'unit_id' => $detail->unit_id,
                'store_id' => $detail->purchaseInvoice?->store_id,
                'notes' => $notes,
                'transaction_date' => $detail->purchaseInvoice->date ?? now(),
                'transactionable_id' => $detail->purchase_invoice_id,
                'transactionable_type' => PurchaseInvoice::class,
            ]);
        }
        // } catch (Exception $e) {
        //     DB::rollBack(); // Rollback transaction on error
        //     Log::error("Error migrating transaction for Purchase Invoice ID: {$detail->purchase_invoice_id}, Product ID: {$detail->product_id}. Error: " . $e->getMessage());
        // }
    }


    /**
     * Updates the package_size in purchase_invoice_details based on unit_prices.
     *
     * @return void
     */
    public static function updatePackageSizeInPurchaseDetails()
    {
        // Retrieve all purchase invoice details
        $purchaseDetails = PurchaseInvoiceDetail::all();

        foreach ($purchaseDetails as $detail) {
            DB::beginTransaction();

            try {
                // Find the matching unit price for the product and unit
                $unitPrice = UnitPrice::where('product_id', $detail->product_id)
                    ->where('unit_id', $detail->unit_id)
                    ->first();

                if ($unitPrice) {
                    $detail->update(['package_size' => $unitPrice->package_size]);
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
            }
        }
    }
}
