<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Queries;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Services\MultiProductsInventoryService;

final class GetInvoiceReturnableItemsQuery
{
    public function execute(int $invoiceId): array
    {
        $invoice = PurchaseInvoice::with([
            'purchaseInvoiceDetails.product',
            'purchaseInvoiceDetails.unit',
            'store',
            'supplier',
        ])->findOrFail($invoiceId);

        $items = [];

        foreach ($invoice->purchaseInvoiceDetails as $detail) {
            $returnedQuantity = (float) PurchaseReturnDetail::where('purchase_invoice_detail_id', $detail->id)
                ->whereHas('purchaseReturn', fn($q) => $q->where('status', PurchaseReturn::STATUS_APPROVED))
                ->sum('quantity');

            $remainingInvoiceQty = max(0.0, (float) $detail->quantity - $returnedQuantity);

            $availableShelfStock = MultiProductsInventoryService::getRemainingQty(
                (int) $detail->product_id,
                (int) $detail->unit_id,
                (int) $invoice->store_id
            );

            $maxAllowedReturnQty = min($remainingInvoiceQty, $availableShelfStock);

            $items[] = [
                'purchase_invoice_detail_id' => $detail->id,
                'product_id'                 => $detail->product_id,
                'product_name'               => $detail->product?->name,
                'product_code'               => $detail->product?->code,
                'unit_id'                    => $detail->unit_id,
                'unit_name'                  => $detail->unit?->name,
                'package_size'               => (float) $detail->package_size,
                'purchased_quantity'         => (float) $detail->quantity,
                'already_returned_quantity'  => $returnedQuantity,
                'remaining_invoice_quantity' => $remainingInvoiceQty,
                'available_shelf_stock'      => $availableShelfStock,
                'max_allowed_return_qty'     => $maxAllowedReturnQty,
                'unit_price'                 => (float) $detail->price,
                'quantity'                   => 0.0,
                'total_price'                => 0.0,
            ];
        }

        return [
            'invoice_id'    => $invoice->id,
            'invoice_no'    => $invoice->invoice_no,
            'supplier_id'   => $invoice->supplier_id,
            'supplier_name' => $invoice->supplier?->name,
            'store_id'      => $invoice->store_id,
            'store_name'    => $invoice->store?->name,
            'items'         => $items,
        ];
    }
}
