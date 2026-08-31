<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Rules;

use App\Models\PurchaseInvoiceDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ReturnQuantityWithinLimitRule implements ValidationRule
{
    public function __construct(
        private readonly ?int $purchaseInvoiceId,
        private readonly ?int $productId = null,
        private readonly ?int $purchaseInvoiceDetailId = null,
        private readonly ?int $excludeReturnId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $qty = (float) $value;

        if ($qty <= 0) {
            $fail('Return quantity must be greater than zero.');
            return;
        }

        if (! $this->purchaseInvoiceId) {
            return;
        }

        $detail = null;
        if ($this->purchaseInvoiceDetailId) {
            $detail = PurchaseInvoiceDetail::find($this->purchaseInvoiceDetailId);
        } elseif ($this->productId) {
            $detail = PurchaseInvoiceDetail::query()
                ->where('purchase_invoice_id', $this->purchaseInvoiceId)
                ->where('product_id', $this->productId)
                ->first();
        }

        if (! $detail) {
            return;
        }

        $maxReturnable = $detail->getRemainingReturnableQuantityForReturn($this->excludeReturnId);

        if ($qty > $maxReturnable) {
            $productName = $detail->product?->name ?? "Product #{$detail->product_id}";
            $fail("Return quantity ({$qty}) for [{$productName}] exceeds the remaining invoice limit ({$maxReturnable}).");
        }
    }
}
