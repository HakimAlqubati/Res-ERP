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
            throw new PurchaseReturnValidationException('No items provided');
        }

        // 2. Validate individual item fields
        foreach ($context->items as $item) {
            if ($item->quantity <= 0) {
                throw new PurchaseReturnValidationException('Invalid return quantity');
            }

            if ($item->unitPrice < 0) {
                throw new PurchaseReturnValidationException('Invalid unit price');
            }
        }

        // 3. If linked to an original purchase invoice, strictly enforce invoice boundaries
        if ($context->purchaseInvoice) {
            // Check store consistency
            if ((int) $context->purchaseInvoice->store_id !== (int) $context->storeId) {
                throw new PurchaseReturnValidationException('Store mismatch');
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
                    throw new PurchaseReturnValidationException('Product not in invoice');
                }

                // Verify return unit price does not exceed original purchased rate
                $invPackageSize = max(1.0, (float) ($invoiceDetail->package_size ?? 1.0));
                $itemPackageSize = max(1.0, (float) ($item->packageSize ?? 1.0));
                $maxAllowedUnitPrice = round(((float) $invoiceDetail->price / $invPackageSize) * $itemPackageSize, 4);

                if ($item->unitPrice > $maxAllowedUnitPrice) {
                    throw new PurchaseReturnValidationException('Price exceeds invoice');
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
                    throw new ReturnQuantityExceededException('Exceeds invoice limit');
                }
            }
        }

        return $next($context);
    }
}
