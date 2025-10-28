<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray($request)
    {
        // Include both raw fields & computed accessors (appends) for stable API
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'count_days'         => $this->count_days,
            'description'        => $this->description,
            'active'             => (bool) $this->active,
            'type'               => $this->type,
            'balance_period'     => $this->balance_period,
            'is_paid'            => (bool) $this->is_paid,
            'created_by'         => $this->created_by,
            'updated_by'         => $this->updated_by,
            'created_at'         => optional($this->created_at)->toISOString(),
            'updated_at'         => optional($this->updated_at)->toISOString(),

            // Accessors defined in your model:
            'type_label'         => $this->type_label,
            'balance_period_label' => $this->balance_period_label,
        ];
    }
}
