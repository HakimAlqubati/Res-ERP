<?php

namespace App\Http\Resources\HR\Maintenance;

use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentTypeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => new EquipmentCategoryResource($this->whenLoaded('category')),
            'is_active' => (bool) ($this->is_active ?? true),
            'created_at' => $this->created_at,
        ];
    }
}
