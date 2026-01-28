<?php

namespace App\Http\Resources\Inventory\StockAdjustment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'product_code' => $this->product?->code,
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit?->name,
            'package_size' => (float) $this->package_size,
            'quantity' => (float) $this->quantity,
            'adjustment_type' => $this->adjustment_type,
            'adjustment_date' => $this->adjustment_date,
            'store_id' => $this->store_id,
            'store_name' => $this->store?->name,
            'reason_id' => $this->reason_id,
            'notes' => $this->notes,
            'source_id' => $this->source_id,
            'source_type' => $this->source_type,
            'created_by' => $this->created_by,
            'creator_name' => $this->createdBy?->name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
