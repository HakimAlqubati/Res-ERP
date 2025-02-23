<?php

namespace App\Services\MigrationScripts;

use App\Models\PurchaseInvoiceDetail;
use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoice;
use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        Log::info('Starting inventory transactions migration...');

        // Retrieve all purchase invoice details
        $purchaseDetails = PurchaseInvoiceDetail::with('purchaseInvoice.store')->get();
        foreach ($purchaseDetails as $detail) {
            self::migrateTransactionForDetail($detail);
        }

        Log::info('Inventory transactions migration completed.');
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

            // DB::commit(); // Commit transaction if successful

            Log::info("Inventory transaction created for Purchase Invoice ID: {$detail->purchase_invoice_id}, Product ID: {$detail->product_id}");
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
        Log::info('Starting package_size update for purchase_invoice_details...');

        // Retrieve all purchase invoice details
        $purchaseDetails = PurchaseInvoiceDetail::all();

        foreach ($purchaseDetails as $detail) {
            DB::beginTransaction(); // Start transaction for each update

            try {
                // Find the matching unit price for the product and unit
                $unitPrice = UnitPrice::where('product_id', $detail->product_id)
                    ->where('unit_id', $detail->unit_id)
                    ->first();

                if ($unitPrice) {
                    // Update package_size in purchase_invoice_details
                    $detail->update(['package_size' => $unitPrice->package_size]);

                    Log::info("Updated package_size for Purchase Invoice Detail ID: {$detail->id} to {$unitPrice->package_size}");
                } else {
                    Log::warning("No matching unit price found for Purchase Invoice Detail ID: {$detail->id}, Product ID: {$detail->product_id}, Unit ID: {$detail->unit_id}");
                }

                DB::commit(); // Commit transaction
            } catch (Exception $e) {
                DB::rollBack(); // Rollback on error
                Log::error("Error updating package_size for Purchase Invoice Detail ID: {$detail->id}. Error: " . $e->getMessage());
            }
        }

        Log::info('Package_size update for purchase_invoice_details completed.');
    }
}
