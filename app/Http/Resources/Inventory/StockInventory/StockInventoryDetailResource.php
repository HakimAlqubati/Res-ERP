<?php

namespace App\Http\Resources\Inventory\StockInventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockInventoryDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_inventory_id' => $this->stock_inventory_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'product_code' => $this->product?->code,
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit?->name,
            'system_quantity' => (float) $this->system_quantity,
            'physical_quantity' => (float) $this->physical_quantity,
            'difference' => (float) $this->difference,
            'package_size' => (float) $this->package_size,
            'is_adjustmented' => (bool) $this->is_adjustmented,
            // 'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            // 'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
