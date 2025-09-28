<?php

namespace App\Http\Resources\HR\Maintenance;

use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'equipment_code_start_with' => $this->equipment_code_start_with ?? null,
            'is_active' => (bool) ($this->is_active ?? true),
        ];
    }
}
