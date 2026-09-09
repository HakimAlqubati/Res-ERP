<?php

namespace App\Rules\Orders;

use App\Models\OrderDetails;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProductInOriginalOrder implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?int $originalOrderId,
        protected ?int $unitId = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($this->originalOrderId) || blank($value)) {
            return;
        }

        $query = OrderDetails::where('order_id', $this->originalOrderId)
            ->where('product_id', $value);

        // If a specific unit is provided, we can strictly check if this product-unit combination exists
        if (!blank($this->unitId)) {
            $query->where('unit_id', $this->unitId);
        }

        if (! $query->exists()) {
            $fail(__('The selected product does not exist in the original order.'));
        }
    }
}
