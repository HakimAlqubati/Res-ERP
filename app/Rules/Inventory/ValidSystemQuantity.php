<?php

namespace App\Rules\Inventory;

use App\Services\MultiProductsInventoryService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidSystemQuantity implements ValidationRule
{
    protected int $storeId;
    protected int $productId;
    protected int $unitId;

    public function __construct(int $storeId, int $productId, int $unitId)
    {
        $this->storeId = $storeId;
        $this->productId = $productId;
        $this->unitId = $unitId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get actual quantity from inventory summary
        $actualQuantity = MultiProductsInventoryService::quickReport($this->storeId, $this->productId, $this->unitId)[0][0]['remaining_qty'];

        // Check if system_quantity matches actual quantity
        if ((float) $value !== (float) $actualQuantity) {
            $fail("الكمية النظامية ({$value}) لا تطابق الكمية الفعلية في المخزن ({$actualQuantity})");
        }
    }
}
