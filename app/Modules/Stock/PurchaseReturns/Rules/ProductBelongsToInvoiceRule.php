<?php

declare(strict_types=1);

namespace App\Modules\Stock\PurchaseReturns\Rules;

use App\Models\PurchaseInvoiceDetail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ProductBelongsToInvoiceRule implements ValidationRule
{
    public function __construct(
        private readonly ?int $purchaseInvoiceId
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->purchaseInvoiceId || empty($value)) {
            return;
        }

        $exists = PurchaseInvoiceDetail::query()
            ->where('purchase_invoice_id', $this->purchaseInvoiceId)
            ->where('product_id', (int) $value)
            ->exists();

        if (! $exists) {
            $fail('The selected product was not purchased in the referenced invoice.');
        }
    }
}
