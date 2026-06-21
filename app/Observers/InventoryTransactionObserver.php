<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Models\ProductItem;
use App\Models\PurchaseInvoice;
use App\Modules\Stock\Jobs\SyncPriceOnNewStockEntryJob;
use App\Modules\Stock\Jobs\SyncProductCurrentBatchPriceJob;
use Spatie\Multitenancy\Contracts\IsTenant;
use Throwable;

class InventoryTransactionObserver
{
    // public function __construct(
    // ) {}

    public function created(InventoryTransaction $transaction)
    {
        \Illuminate\Support\Facades\Log::info('Observer is working! Movement: ' . $transaction->movement_type);
        // $tenantId = app(IsTenant::class)::current()?->id;
        // \Illuminate\Support\Facades\Log::info('Tenant ID: ' . $tenantId);
        // إذا كانت الحركة دخول (in) -> نستدعي أكشن الدخول
        if ($transaction->movement_type === InventoryTransaction::MOVEMENT_IN) {
            \Illuminate\Support\Facades\Log::info('Dispatching SyncPriceOnNewStockEntryJob');
            SyncPriceOnNewStockEntryJob::dispatch($transaction->id,
                $transaction->store_id,
                // $tenantId
            );
        }
        // إذا كانت الحركة خروج (out) -> نستدعي أكشن الخروج
        elseif ($transaction->movement_type === InventoryTransaction::MOVEMENT_OUT) {
            \Illuminate\Support\Facades\Log::info('Dispatching SyncProductCurrentBatchPriceJob');
            SyncProductCurrentBatchPriceJob::dispatch($transaction->product_id,
                $transaction->store_id,
                // $tenantId
            );
        }
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
