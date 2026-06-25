<?php

namespace App\Modules\HR\WorkPeriods\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkPeriodResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'branch_id'         => $this->branch_id,
            'branch_name'       => $this->whenLoaded('branch', function () {
                return $this->branch->name;
            }),
            'active'            => $this->active,
            'description'       => $this->description,
            'start_at'          => $this->start_at,
            'end_at'            => $this->end_at,
            'day_and_night'     => $this->day_and_night,
            'supposed_duration' => $this->supposed_duration,
            'created_by'        => $this->created_by,
            'updated_by'        => $this->updated_by,
        ];
    }
}
