<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use App\Modules\Stock\PurchaseReturns\Exceptions\ReturnQuantityExceededException;
use Closure;

final class ValidateQuantityNotExceedingInvoicePipe
{
    public function handle(PurchaseReturnPipelineContext $context, Closure $next)
    {
        if (! $context->purchaseInvoice) {
            return $next($context);
        }

        $invoiceDetails = $context->purchaseInvoice->purchaseInvoiceDetails->keyBy('id');

        foreach ($context->items as $item) {
            if (! $item->purchaseInvoiceDetailId) {
                continue;
            }

            $invoiceDetail = $invoiceDetails->get($item->purchaseInvoiceDetailId);
            if (! $invoiceDetail) {
                throw new ReturnQuantityExceededException(
                    "Invoice detail #{$item->purchaseInvoiceDetailId} does not belong to invoice #{$context->purchaseInvoice->id}."
                );
            }

            $currentReturnId = $context->purchaseReturn?->id;

            $previouslyReturnedQty = (float) PurchaseReturnDetail::query()
                ->where('purchase_invoice_detail_id', $item->purchaseInvoiceDetailId)
                ->when($currentReturnId, fn($q) => $q->where('purchase_return_id', '!=', $currentReturnId))
                ->whereHas('purchaseReturn', fn($q) => $q->where('status', PurchaseReturn::STATUS_APPROVED))
                ->sum('quantity');

            $maxReturnableQty = max(0.0, (float) $invoiceDetail->quantity - $previouslyReturnedQty);

            if ($item->quantity > $maxReturnableQty) {
                $productName = $invoiceDetail->product?->name ?? "Product #{$item->productId}";
                throw new ReturnQuantityExceededException(
                    "Return quantity for [{$productName}] exceeds remaining returnable invoice limit. Allowed: {$maxReturnableQty}, Requested: {$item->quantity}."
                );
            }
        }

        return $next($context);
    }
}
