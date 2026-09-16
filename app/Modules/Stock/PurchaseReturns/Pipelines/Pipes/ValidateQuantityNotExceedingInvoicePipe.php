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
            throw new PurchaseReturnValidationException('A purchase return must contain at least one item with quantity greater than zero.');
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
            $invoiceNo = $context->purchaseInvoice->invoice_no ?? "ID #{$context->purchaseInvoice->id}";

            // Check store consistency
            if ((int) $context->purchaseInvoice->store_id !== (int) $context->storeId) {
                $invStoreName = $context->purchaseInvoice->store?->name ?? "Store #{$context->purchaseInvoice->store_id}";
                throw new PurchaseReturnValidationException(
                    "Selected store does not match the store where items were received in Invoice #{$invoiceNo} ({$invStoreName})."
                );
            }

            $invoiceDetails = $context->purchaseInvoice->purchaseInvoiceDetails;

            // Map by detail id and by product_id
            $detailsById = $invoiceDetails->keyBy('id');
            $detailsByProduct = $invoiceDetails->groupBy('product_id');

            // Track aggregated requested quantities in base units per invoice detail line
            $requestedBaseQuantitiesByDetailId = [];

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

                // Verify return unit price does not exceed original purchased rate
                $invPackageSize = max(1.0, (float) ($invoiceDetail->package_size ?? 1.0));
                $itemPackageSize = max(1.0, (float) ($item->packageSize ?? 1.0));
                $maxAllowedUnitPrice = round(((float) $invoiceDetail->price / $invPackageSize) * $itemPackageSize, 4);

                if ($item->unitPrice > $maxAllowedUnitPrice) {
                    $product = Product::find($item->productId);
                    $productName = $product?->name ?? "Product #{$item->productId}";
                    throw new PurchaseReturnValidationException(
                        "Return unit price for product [{$productName}] ({$item->unitPrice}) cannot exceed the original purchased price rate ({$maxAllowedUnitPrice}) in Invoice #{$invoiceNo}."
                    );
                }

                $detailId = (int) $invoiceDetail->id;
                $requestedBaseQty = $item->quantity * $itemPackageSize;
                $requestedBaseQuantitiesByDetailId[$detailId] = ($requestedBaseQuantitiesByDetailId[$detailId] ?? 0.0) + $requestedBaseQty;
            }

            // Verify aggregated base quantities against remaining invoice limits
            $currentReturnId = $context->purchaseReturn?->id;

            foreach ($requestedBaseQuantitiesByDetailId as $detailId => $totalRequestedBaseQty) {
                $invoiceDetail = $detailsById->get($detailId);
                $maxReturnableBaseQty = $invoiceDetail->getRemainingReturnableBaseQuantity($currentReturnId);

                if ($totalRequestedBaseQty > $maxReturnableBaseQty) {
                    $invPackageSize = max(1.0, (float) ($invoiceDetail->package_size ?? 1.0));
                    $productName = $invoiceDetail->product?->name ?? "Product #{$invoiceDetail->product_id}";
                    $purchasedQty = (float) $invoiceDetail->quantity;
                    $maxReturnableInInvUnits = round($maxReturnableBaseQty / $invPackageSize, 4);
                    $requestedInInvUnits = round($totalRequestedBaseQty / $invPackageSize, 4);

                    throw new ReturnQuantityExceededException(
                        "Total return quantity for [{$productName}] ({$requestedInInvUnits} in invoice units) exceeds the remaining returnable limit ({$maxReturnableInInvUnits}) in Invoice #{$invoiceNo}. Purchased in invoice: {$purchasedQty}."
                    );
                }
            }
        }

        return $next($context);
    }
}
