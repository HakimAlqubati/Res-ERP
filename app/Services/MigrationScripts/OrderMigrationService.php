<?php

namespace App\Services\MigrationScripts;

use App\Models\OrderDetails;
use App\Models\UnitPrice;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

use Exception;

class OrderMigrationService
{
    /**
     * Updates the package_size in order_details based on unit_prices.
     */
    public static function updatePackageSizeInOrderDetails()
    {

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
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
            }
        }
    }

    /**
     * Creates inventory transactions for orders.
     * - Retrieves store_id from the nearest movement_date of a purchase transaction.
     */
    public static function createInventoryTransactionOrders()
    {

        // Retrieve all order details that do not have inventory transactions yet
        $orderDetails = OrderDetails::whereHas('order')->get();
        foreach ($orderDetails as $detail) {

            // DB::beginTransaction();

            // try {
            // Find the nearest inventory transaction from purchases
            $nearestTransaction = InventoryTransaction::where('product_id', $detail->product_id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
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
                    'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                    'quantity' => $detail->quantity,
                    'package_size' => $detail->package_size,
                    'price' => $detail->price,
                    'movement_date' => $detail->order->created_at,
                    'unit_id' => $detail->unit_id,
                    'reference_id' => $detail->order_id,
                    'store_id' => $storeId,
                    'notes' => $notes,
                    'transaction_date' => $detail->order->created_at,
                ]);
            }
        }
    }
}
