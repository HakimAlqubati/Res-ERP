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

     //         'parent_ids' => $parentProducts,
    //         'base_product_id' => $purchaseInvoiceDetail->product_id,
    //     ]);

    //     foreach ($parentProducts as $parentProductId) {
    //         try {
    //             $count = ProductCostingService::updateComponentPricesForProduct($parentProductId);
     //         } catch (\Throwable $e) {
     //         }
    //     }
    // }
}
