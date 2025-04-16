<?php

namespace App\Observers;

use App\Models\ProductItem;
use App\Models\PurchaseInvoiceDetail;
use App\Models\InventoryTransaction;
use App\Services\ProductCostingService;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceDetailObserver
{
    /**
     * Handle the PurchaseInvoiceDetail "created" event.
     */
    // public function created(PurchaseInvoiceDetail $purchaseInvoiceDetail): void
    // {
    //     // ✅ تحديث أسعار المنتجات المركبة
    //     $parentProducts = ProductItem::where('product_id', $purchaseInvoiceDetail->product_id)
    //         ->pluck('parent_product_id')
    //         ->unique();

    //     Log::info('[🔄 PurchaseInvoiceDetailObserver] Parent Products:', [
    //         'parent_ids' => $parentProducts,
    //         'base_product_id' => $purchaseInvoiceDetail->product_id,
    //     ]);

    //     foreach ($parentProducts as $parentProductId) {
    //         try {
    //             $count = ProductCostingService::updateComponentPricesForProduct($parentProductId);
    //             Log::info("✅ [Invoice #{$purchaseInvoiceDetail->purchaseInvoice->id}] Updated prices for {$count} components of composite product ID {$parentProductId}");
    //         } catch (\Throwable $e) {
    //             Log::error("❌ Error updating costing for composite product {$parentProductId}: {$e->getMessage()}");
    //         }
    //     }
    // }
}
