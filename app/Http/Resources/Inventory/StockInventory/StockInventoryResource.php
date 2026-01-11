<?php

namespace App\Http\Resources\Inventory\StockInventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_date' => $this->inventory_date,
            'store_id' => $this->store_id,
            'store_name' => $this->store?->name,
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_user_name' => $this->responsibleUser?->name,
            'finalized' => (bool) $this->finalized,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,

            // Appended attributes
            'details_count' => $this->details_count,
            'categories_names' => $this->categories_names,

            // Relationships (only when loaded)
            'details' => StockInventoryDetailResource::collection($this->whenLoaded('details')),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
