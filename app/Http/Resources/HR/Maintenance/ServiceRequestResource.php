<?php

namespace App\Http\Resources\HR\Maintenance;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'description'   => $this->description,
            'branch_id'     => $this->branch_id,
            'branch'        => $this->whenLoaded('branch'),
            'branch_area_id'=> $this->branch_area_id,
            'branch_area'   => $this->whenLoaded('branchArea'),
            'assigned_to'   => $this->assigned_to,
            'assignee'      => $this->whenLoaded('assignedTo'),
            'urgency'       => $this->urgency,
            'impact'        => $this->impact,
            'status'        => $this->status,
            'accepted'      => (bool)$this->accepted,
            'equipment_id'  => $this->equipment_id,
            'equipment'     => $this->whenLoaded('equipment'),
            'photos_count'  => $this->photos_count ?? $this->photos()->count(),
            'first_photo'   => $this->first_photo_url,
            'created_by'    => $this->created_by,
            'updated_by'    => $this->updated_by,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
