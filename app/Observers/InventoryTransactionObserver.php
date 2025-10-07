<?php

namespace App\Observers;

use App\Models\ProductItem;
use Throwable;
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
            $parentProducts = ProductItem::where('product_id', $inventoryTransaction->product_id)
                ->pluck('parent_product_id')
                ->unique();

        
            foreach ($parentProducts as $parentProductId) {
                try {
                    // $count = \App\Services\ProductCostingService::updateComponentPricesForProduct($parentProductId);
                 } catch (Throwable $e) {
                 }
            }
        }
    }
}
