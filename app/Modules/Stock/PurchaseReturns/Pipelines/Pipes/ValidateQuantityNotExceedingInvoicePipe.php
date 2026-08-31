<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use App\Modules\Stock\PurchaseReturns\Exceptions\PurchaseReturnValidationException;
use App\Modules\Stock\PurchaseReturns\Exceptions\ReturnQuantityExceededException;
use Closure;

final class ValidateQuantityNotExceedingInvoicePipe
{
    public function handle(PurchaseReturnPipelineContext $context, Closure $next)
    {
        // 1. Guard: Check if items collection is empty
        if ($context->items->isEmpty()) {
            throw new PurchaseReturnValidationException('A purchase return must contain at least one item.');
        }

        // 2. Validate individual item fields
        foreach ($context->items as $item) {
            if ($item->quantity <= 0) {
                $productName = Product::find($item->productId)?->name ?? "Product #{$item->productId}";
                throw new PurchaseReturnValidationException("Return quantity for product [{$productName}] must be greater than zero.");
            }

            if ($item->unitPrice < 0) {
                $productName = Product::find($item->productId)?->name ?? "Product #{$item->productId}";
                throw new PurchaseReturnValidationException("Unit price for product [{$productName}] cannot be negative.");
            }
        }

        // 3. If linked to an original purchase invoice, strictly enforce invoice boundaries
        if ($context->purchaseInvoice) {
            $invoiceDetails = $context->purchaseInvoice->purchaseInvoiceDetails;
            $invoiceNo = $context->purchaseInvoice->invoice_no ?? "ID #{$context->purchaseInvoice->id}";

            // Map by detail id and by product_id
            $detailsById = $invoiceDetails->keyBy('id');
            $detailsByProduct = $invoiceDetails->groupBy('product_id');

            // Track aggregated requested quantities per invoice detail line
            $requestedQuantitiesByDetailId = [];

            foreach ($context->items as $item) {
                $invoiceDetail = null;

                // Attempt 1: Find by explicit detail id
                if ($item->purchaseInvoiceDetailId && $detailsById->has($item->purchaseInvoiceDetailId)) {
                    $invoiceDetail = $detailsById->get($item->purchaseInvoiceDetailId);
                }

                // Attempt 2: Find by product_id
                if (! $invoiceDetail && $detailsByProduct->has($item->productId)) {
                    $matchingDetails = $detailsByProduct->get($item->productId);
                    // Match by unit_id if possible, or take the first matching product detail
                    $invoiceDetail = $matchingDetails->firstWhere('unit_id', $item->unitId) ?? $matchingDetails->first();
                }

                // If product was NOT found in the invoice, reject immediately
                if (! $invoiceDetail) {
                    $product = Product::find($item->productId);
                    $productName = $product?->name ?? "Product #{$item->productId}";
                    throw new PurchaseReturnValidationException(
                        "Product [{$productName}] does not exist in the selected Purchase Invoice #{$invoiceNo}. You cannot return products that were not purchased in this invoice."
                    );
                }

                $detailId = (int) $invoiceDetail->id;
                $requestedQuantitiesByDetailId[$detailId] = ($requestedQuantitiesByDetailId[$detailId] ?? 0.0) + $item->quantity;
            }

            // Verify aggregated quantities against remaining invoice limits
            $currentReturnId = $context->purchaseReturn?->id;

            foreach ($requestedQuantitiesByDetailId as $detailId => $totalRequestedQty) {
                $invoiceDetail = $detailsById->get($detailId);

                $previouslyReturnedQty = (float) PurchaseReturnDetail::query()
                    ->where('purchase_invoice_detail_id', $detailId)
                    ->when($currentReturnId, fn($q) => $q->where('purchase_return_id', '!=', $currentReturnId))
                    ->whereHas('purchaseReturn', fn($q) => $q->where('status', PurchaseReturn::STATUS_APPROVED))
                    ->sum('quantity');

                $purchasedQty = (float) $invoiceDetail->quantity;
                $maxReturnableQty = max(0.0, $purchasedQty - $previouslyReturnedQty);

                if ($totalRequestedQty > $maxReturnableQty) {
                    $productName = $invoiceDetail->product?->name ?? "Product #{$invoiceDetail->product_id}";
                    throw new ReturnQuantityExceededException(
                        "Total return quantity for [{$productName}] ({$totalRequestedQty}) exceeds the remaining returnable limit ({$maxReturnableQty}) in Invoice #{$invoiceNo}. Purchased: {$purchasedQty}, Already Returned: {$previouslyReturnedQty}."
                    );
                }
            }
        }

        return $next($context);
    }
}
