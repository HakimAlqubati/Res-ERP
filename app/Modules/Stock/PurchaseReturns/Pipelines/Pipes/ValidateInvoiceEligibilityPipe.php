<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Pipelines\Pipes;

use App\Models\PurchaseInvoice;
use App\Models\Store;
use App\Models\Supplier;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\PurchaseReturnPipelineContext;
use App\Modules\Stock\PurchaseReturns\Exceptions\PurchaseReturnValidationException;
use Closure;

final class ValidateInvoiceEligibilityPipe
{
    public function handle(PurchaseReturnPipelineContext $context, Closure $next)
    {
        $context->supplier = Supplier::find($context->supplierId);
        if (! $context->supplier) {
            throw new PurchaseReturnValidationException('Selected supplier does not exist.');
        }

        $context->store = Store::find($context->storeId);
        if (! $context->store) {
            throw new PurchaseReturnValidationException('Selected store does not exist.');
        }

        if ($context->purchaseInvoiceId) {
            $invoice = PurchaseInvoice::with('purchaseInvoiceDetails')->find($context->purchaseInvoiceId);

            if (! $invoice) {
                throw new PurchaseReturnValidationException('Referenced purchase invoice not found.');
            }

            if ($invoice->cancelled) {
                throw new PurchaseReturnValidationException('Cannot process return against a cancelled purchase invoice.');
            }

            if ((int) $invoice->supplier_id !== (int) $context->supplierId) {
                throw new PurchaseReturnValidationException('Supplier does not match the original purchase invoice supplier.');
            }

            $context->purchaseInvoice = $invoice;
        }

        return $next($context);
    }
}
