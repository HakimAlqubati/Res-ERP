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
            'assignees'     => $this->whenLoaded('assignees', fn() =>
                $this->assignees->map(fn($e) => [
                    'id'         => $e->id,
                    'name'       => $e->name,
                    'is_primary' => (bool) $e->pivot->is_primary,
                ])
            ),
            'primary_assignee' => $this->whenLoaded('assignees', fn() =>
                $this->assignees->firstWhere('pivot.is_primary', true)
                    ? ['id' => $this->assignees->firstWhere('pivot.is_primary', true)->id,
                       'name' => $this->assignees->firstWhere('pivot.is_primary', true)->name]
                    : null
            ),
            'urgency'       => $this->urgency,
            'impact'        => $this->impact,
            'status'        => $this->status,
            'accepted'      => (bool)$this->accepted,
            'equipment_id'  => $this->equipment_id,
            'equipment'     => $this->whenLoaded('equipment'),
            'photos_count'  => $this->photos_count ?? $this->photos()->count(),
            'first_photo'   => $this->first_photo_url,
            'created_by'    => $this?->createdBy?->name,
            'updated_by'    => $this?->updatedBy?->name,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
