<?php

namespace App\Validators\Inventory;

use App\Models\Order;
use App\Models\StockInventory;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

class StockInventoryCreationValidator
{
    /**
     * Error messages for validation failures.
     */
    public const ERR_DEFAULT_STORE_HAS_ORDERED = 'Cannot create inventory for "%s". There are %d pending (ordered) orders.';
    public const ERR_BRANCH_HAS_PROCESSING = 'Cannot create inventory for "%s". The associated branch has %d processing orders.';

    /**
     * Validate the creation of a new Stock Inventory.
     * Throws a ValidationException if any condition is violated.
     *
     * @param StockInventory $inventory
     * @throws ValidationException
     */
    public static function validate(StockInventory $inventory): void
    {
        // Ensure we have a store instance to check against
        $store = $inventory->store ?? Store::find($inventory->store_id);

        if (!$store) {
            return;
        }

        // self::checkDefaultStoreOrders($store);
        self::checkBranchProcessingOrders($store);
    }

    /**
     * Prevent inventory in the default store if there are any 'ordered' orders.
     *
     * @param Store $store
     * @throws ValidationException
     */
    private static function checkDefaultStoreOrders(Store $store): void
    {
        // Only apply this rule if the store is the default one
        if (!$store->default_store) {
            return;
        }

        $orderedOrdersCount = Order::where('status', Order::ORDERED)
        ->whereHas('orderDetails2')
        ->count();

        if ($orderedOrdersCount > 0) {
            $message = sprintf(self::ERR_DEFAULT_STORE_HAS_ORDERED, $store->name, $orderedOrdersCount);
            
            throw ValidationException::withMessages([
                'store_id' => $message,
            ]);
        }
    }

    /**
     * Prevent inventory if there are 'processing' orders in the branch linked to this store.
     *
     * @param Store $store
     * @throws ValidationException
     */
    private static function checkBranchProcessingOrders(Store $store): void
    {
        $processingOrdersCount = Order::where('status', Order::PROCESSING)
            ->whereHas('branch', function ($query) use ($store) {
                $query->where('store_id', $store->id);
            })
            ->count();

        if ($processingOrdersCount > 0) {
            $message = sprintf(self::ERR_BRANCH_HAS_PROCESSING, $store->name, $processingOrdersCount);

            throw ValidationException::withMessages([
                'store_id' => $message,
            ]);
        }
    }
}
