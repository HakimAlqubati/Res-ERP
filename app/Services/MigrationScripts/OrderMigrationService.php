<?php

namespace App\Services\MigrationScripts;

use App\Models\OrderDetails;
use App\Models\UnitPrice;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderMigrationService
{
    /**
     * Updates the package_size in order_details based on unit_prices.
     */
    public static function updatePackageSizeInOrderDetails()
    {
        Log::info('Starting package_size update for order_details');

        // Retrieve all order details
        $orderDetails = OrderDetails::all();

        foreach ($orderDetails as $detail) {
            DB::beginTransaction();

            try {
                // Find the matching unit price for the product and unit
                $unitPrice = UnitPrice::where('product_id', $detail->product_id)
                    ->where('unit_id', $detail->unit_id)
                    ->first();

                if ($unitPrice) {
                    $detail->update(['package_size' => $unitPrice->package_size]);

                    Log::info("Updated package_size for Order Detail ID: {$detail->id} to {$unitPrice->package_size}");
                } else {
                    Log::warning("No matching unit price found for Order Detail ID: {$detail->id}, Product ID: {$detail->product_id}, Unit ID: {$detail->unit_id}");
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("Error updating package_size for Order Detail ID: {$detail->id}. Error: " . $e->getMessage());
            }
        }

        Log::info('Completed package_size update for order_details.');
    }

    /**
     * Creates inventory transactions for orders.
     * - Retrieves store_id from the nearest movement_date of a purchase transaction.
     */
    public static function createInventoryTransactionOrders()
    {
        Log::info('Starting inventory transaction creation for orders...');

        // Retrieve all order details that do not have inventory transactions yet
        $orderDetails = OrderDetails::whereHas('order')->get();
        foreach ($orderDetails as $detail) {

            // DB::beginTransaction();

            // try {
                // Find the nearest inventory transaction from purchases
                $nearestTransaction = InventoryTransaction::where('product_id', $detail->product_id)
                    ->where('movement_type', InventoryTransaction::MOVEMENT_PURCHASE_INVOICE)
                    ->orderBy('movement_date', 'desc') // Get the most recent purchase transaction
                    ->first();

                $storeId = $nearestTransaction?->store_id ?? null; // Default to null if not found

                // Prepare transaction notes
                $notes = "Order ID {$detail->order_id}";
                if ($storeId) {
                    $notes .= " - Store: {$storeId}";
                }

                if ($detail->package_size) {
                    // Create new inventory transaction for the order
                    InventoryTransaction::create([
                        'product_id' => $detail->product_id,
                        'movement_type' => InventoryTransaction::MOVEMENT_ORDERS,
                        'quantity' => $detail->quantity,
                        'package_size' => $detail->package_size,
                        'price' => $detail->price,
                        'movement_date' => $detail->order->created_at,
                        'unit_id' => $detail->unit_id,
                        'reference_id' => $detail->order_id,
                        'store_id' => $storeId, // Get from nearest purchase transaction
                        'notes' => $notes,
                        'transaction_date' => $detail->order->created_at,
                    ]);

                    // DB::commit();
                    Log::info("Created inventory transaction for Order ID: {$detail->order_id}, Product ID: {$detail->product_id}");
                }
            // } catch (Exception $e) {
            //     DB::rollBack();
            //     Log::error("Error creating inventory transaction for Order ID: {$detail->order_id}. Error: " . $e->getMessage());
            // }
        }

        Log::info('Completed inventory transaction creation for orders.');
    }
}
