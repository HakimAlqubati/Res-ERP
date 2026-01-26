<?php

namespace App\Observers;

use App\Models\ProductItem;
use Throwable;
use App\Models\InventoryTransaction;
use App\Models\PurchaseInvoice;
use App\Services\ProductCostingService;
use App\Services\Inventory\Summary\InventorySummaryUpdater;
use Illuminate\Support\Facades\Log;

class InventoryTransactionObserver
{
    public function __construct(
        private InventorySummaryUpdater $summaryUpdater
    ) {}

    public function created(InventoryTransaction $inventoryTransaction)
    {
        // تحديث ملخص المخزون
        // $this->summaryUpdater->onTransactionCreated($inventoryTransaction);

        // ✅ تحديث أسعار المنتجات المركبة بعد إضافة حركة شراء جديدة
        // if ($inventoryTransaction->movement_type === InventoryTransaction::MOVEMENT_IN && $inventoryTransaction->transactionable_type === PurchaseInvoice::class) {
        //     $parentProducts = ProductItem::where('product_id', $inventoryTransaction->product_id)
        //         ->pluck('parent_product_id')
        //         ->unique();

        //     foreach ($parentProducts as $parentProductId) {
        //         try {
        //             // $count = \App\Services\ProductCostingService::updateComponentPricesForProduct($parentProductId);
        //         } catch (Throwable $e) {
        //         }
        //     }
        // }
    }

    // لا نستخدم حدث التعديل لتجنب التحديث المضاعف
    // public function updated(InventoryTransaction $inventoryTransaction)
    // {
    //     $this->summaryUpdater->onTransactionUpdated(
    //         $inventoryTransaction,
    //         $inventoryTransaction->getOriginal()
    //     );
    // }

    public function deleted(InventoryTransaction $inventoryTransaction)
    {
        // $this->summaryUpdater->onTransactionDeleted($inventoryTransaction);
    }

    public function restored(InventoryTransaction $inventoryTransaction)
    {
        // $this->summaryUpdater->onTransactionRestored($inventoryTransaction);
    }
}
