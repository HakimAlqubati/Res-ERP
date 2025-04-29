<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoice;
use App\Services\ProductCostingService;
use Illuminate\Support\Facades\Log;

class InventoryTransactionObserver
{
    public function created(InventoryTransaction $inventoryTransaction)
    {

        // ✅ تحديث أسعار المنتجات المركبة بعد إضافة حركة شراء جديدة
        if ($inventoryTransaction->movement_type === InventoryTransaction::MOVEMENT_IN && $inventoryTransaction->transactionable_type === PurchaseInvoice::class) {
            $parentProducts = \App\Models\ProductItem::where('product_id', $inventoryTransaction->product_id)
                ->pluck('parent_product_id')
                ->unique();

            Log::info('[🎯 InventoryTransaction] Parent composite products affected:', [
                'affected_parents' => $parentProducts,
                'base_product' => $inventoryTransaction->product_id,
                'from' => self::class,
            ]);

            foreach ($parentProducts as $parentProductId) {
                try {
                    $count = \App\Services\ProductCostingService::updateComponentPricesForProduct($parentProductId);
                    Log::info("✅ [InventoryTxn→PurchaseInvoice #{$inventoryTransaction->transactionable_id}] Updated {$count} components for composite product ID {$parentProductId}");
                } catch (\Throwable $e) {
                    Log::error("❌ Error updating costing for composite product ID {$parentProductId}: {$e->getMessage()}");
                }
            }
        }
    }
}
