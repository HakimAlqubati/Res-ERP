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
        private readonly ?float $packageSize = 1.0,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $qty = (float) $value;

        if ($qty < 0) {
            $fail('Return quantity cannot be negative.');
            return;
        }

        // 0 will be filtered out on submit, so it shouldn't trigger a failure
        if ($qty == 0) {
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

        $pkgSize = max(1.0, (float) ($this->packageSize ?? 1.0));
        $maxReturnable = $detail->getRemainingReturnableQuantityForReturn($this->excludeReturnId, $pkgSize);

        if ($qty > $maxReturnable) {
            $productName = $detail->product?->name ?? "Product #{$detail->product_id}";
            $fail("Return quantity ({$qty}) for [{$productName}] exceeds the remaining invoice limit ({$maxReturnable}).");
        }
    }
}
