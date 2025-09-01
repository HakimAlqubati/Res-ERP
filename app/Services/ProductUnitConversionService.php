<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Don't forget to import Log

class ProductUnitConversionService // Suggesting a name that indicates its purpose: migrating old unit definitions
{
    /**
     * Updates unit_id and package_size for a specific product
     * across multiple transactional and master data tables.
     *
     * IMPORTANT CONSIDERATIONS:
     * 1. FULL BACKUP: Always perform a full database backup before running this.
     * 2. DATA INTEGRITY: Modifying historical transactional data can severely impact
     * the accuracy of past reports, costing (FIFO/LIFO), and audit trails.
     * This approach assumes that the 'from' unit_id and package_size represent
     * a *past incorrect definition* that needs to be 'corrected' globally,
     * rather than just moving to a new unit for future transactions.
     * 3. TESTING: Test thoroughly in a non-production environment first.
     * 4. UNIT CONVERSION: Ensure the new package_size correctly reflects the
     * conversion from the old unit to the new unit, relative to the product's base unit.
     *
     * @param int $productId The ID of the product to update.
     * @param int $fromUnitId The original unit_id to find and replace.
     * @param int $toUnitId The new unit_id to set.
     * @param float $fromPackageSize The original package_size to find and replace.
     * @param float $toPackageSize The new package_size to set.
     * @throws Exception If the transaction fails.
     * @return void
     */
    public function migrateProductUnitAndPackageSize(
        int $productId,
        int $fromUnitId,
        int $toUnitId,
        float $fromPackageSize,
        float $toPackageSize
    ): void {
        Log::info("Attempting to migrate product #{$productId} unit from {$fromUnitId} (pkg: {$fromPackageSize}) to {$toUnitId} (pkg: {$toPackageSize}).");

        try {
            DB::transaction(function () use ($productId, $fromUnitId, $toUnitId, $fromPackageSize, $toPackageSize) {
                $tablesToUpdate = [
                    'unit_prices',
                    'purchase_invoice_details',
                    'orders_details',
                    'goods_received_note_details',
                    'stock_supply_order_details',
                    'stock_issue_order_details',
                    'inventory_transactions',
                    'stock_inventory_details',
                    'stock_adjustment_details',
                    'stock_transfer_order_details',
                ];

                foreach ($tablesToUpdate as $tableName) {
                    $updatedRows = DB::table($tableName)
                        ->where('product_id', $productId)
                        ->where('unit_id', $fromUnitId)
                        ->where('package_size', $fromPackageSize)
                        ->update([
                            'unit_id' => $toUnitId,
                            'package_size' => $toPackageSize,
                        ]);

                    Log::info("Updated {$updatedRows} rows in '{$tableName}' for product #{$productId}.");
                }

                Log::info("✅ Successfully migrated unit_id and package_size for product #{$productId} from unit {$fromUnitId} (pkg {$fromPackageSize}) to unit {$toUnitId} (pkg {$toPackageSize}).");
            });
        } catch (Exception $e) {
            Log::error("❌ Failed to migrate unit_id and package_size for product #{$productId}. Error: " . $e->getMessage());
            // Re-throw the exception to ensure the calling code knows it failed
            throw $e;
        }
    }
}