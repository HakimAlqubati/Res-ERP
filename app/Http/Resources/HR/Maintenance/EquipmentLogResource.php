<?php

namespace App\Http\Resources\HR\Maintenance;

use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'equipment_id'   => $this->equipment_id,
            'equipment_name' => $this->equipment?->name,
            'action'         => $this->action,
            'description'    => $this->description,
            'performed_by'      => $this->performed_by,
            'performed_by_name' => $this->performedBy?->name,
            'created_at'        => $this->created_at,
        ];
    }
}
