<?php

namespace App\Http\Requests\Inventory\StockInventory;

use App\Services\MultiProductsInventoryService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStockInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'inventory_date' => ['sometimes', 'date'],
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
            'responsible_user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'finalized' => ['sometimes', 'boolean'],

            // Details validation (optional for update)
            'details' => ['sometimes', 'array'],
            'details.*.product_id' => ['required_with:details', 'integer', 'exists:products,id'],
            'details.*.unit_id' => ['required_with:details', 'integer', 'exists:units,id'],
            'details.*.physical_quantity' => ['required_with:details', 'numeric', 'min:0'],
            'details.*.system_quantity' => ['required_with:details', 'numeric'],
            'details.*.package_size' => ['required_with:details', 'numeric', 'min:0.01'],
            'details.*.is_adjustmented' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->has('details')) {
                return;
            }

            // Get store_id from request or from existing inventory
            $storeId = $this->input('store_id');
            if (!$storeId && $this->route('id')) {
                $inventory = \App\Models\StockInventory::find($this->route('id'));
                $storeId = $inventory?->store_id;
            }

            if (!$storeId) {
                return;
            }

            $details = $this->input('details', []);

            foreach ($details as $index => $detail) {
                if (!isset($detail['product_id'], $detail['unit_id'], $detail['system_quantity'])) {
                    continue;
                }

                // Get product and unit names
                $product = \App\Models\Product::find($detail['product_id']);
                $unit = \App\Models\Unit::find($detail['unit_id']);

                $productName = $product?->name ?? "Product #{$detail['product_id']}";
                $unitName = $unit?->name ?? "Unit #{$detail['unit_id']}";

                // Get actual quantity from inventory summary
                $actualQuantity = MultiProductsInventoryService::quickReport($storeId, $detail['product_id'], $detail['unit_id'])[0][0]['remaining_qty'];

                $systemQuantity = (float) $detail['system_quantity'];

                dd($systemQuantity, $actualQuantity);
                // Validate system_quantity matches actual inventory
                if ($systemQuantity !== $actualQuantity) {
                    $validator->errors()->add(
                        "details.{$index}.system_quantity",
                        "Product '{$productName}' (Unit: {$unitName}): The system quantity ({$systemQuantity}) does not match the actual inventory quantity ({$actualQuantity})"
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'inventory_date.date' => 'The inventory date must be a valid date',
            'store_id.exists' => 'The selected store does not exist',
            'responsible_user_id.exists' => 'The selected user does not exist',
            'details.*.product_id.required_with' => 'The product is required in details',
            'details.*.product_id.exists' => 'The selected product does not exist',
            'details.*.unit_id.required_with' => 'The unit is required in details',
            'details.*.unit_id.exists' => 'The selected unit does not exist',
            'details.*.physical_quantity.required_with' => 'The physical quantity is required',
            'details.*.physical_quantity.min' => 'The physical quantity must be zero or greater',
            'details.*.package_size.required_with' => 'The package size is required',
            'details.*.package_size.min' => 'The package size must be greater than zero',
        ];
    }
}
