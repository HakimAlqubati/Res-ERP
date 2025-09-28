<?php

namespace App\Http\Resources\HR\Maintenance;

use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'equipment_id' => $this->equipment_id,
            'action'       => $this->action,
            'description'  => $this->description,
            'performed_by' => $this->performed_by,
            'created_at'   => $this->created_at,
        ];
    }
}
