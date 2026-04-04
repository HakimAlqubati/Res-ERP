<?php

namespace App\Modules\HR\Overtime\V2\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee ? $this->employee->name : null,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'hours' => $this->hours,
            'notes' => $this->notes,
            'type' => $this->type,
            'branch_id' => $this->branch_id,
            'status' => $this->status,
            'approved_by' => $this->approvedBy ? $this->approvedBy->name : null,
            'created_by' => $this->createdBy ? $this->createdBy->name : null,
            'approved_at' => $this->approved_at,
            'rejected_by' => $this->rejectedBy ? $this->rejectedBy->name : null,
            'rejected_at' => $this->rejected_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
