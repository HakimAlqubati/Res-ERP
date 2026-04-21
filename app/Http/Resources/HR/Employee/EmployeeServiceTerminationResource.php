<?php

namespace App\Http\Resources\HR\Employee;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeServiceTerminationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'employee_id'        => $this->employee_id,
            'employee_name'      => $this->whenLoaded('employee', fn () => $this->employee->name),
            'termination_date'   => $this->termination_date?->toDateString(),
            'termination_reason' => $this->termination_reason,
            'notes'              => $this->notes,
            'status'             => $this->status,
            'rejection_reason'   => $this->rejection_reason,
            'created_by'         => $this->created_by,
            'creator_name'       => $this->whenLoaded('createdBy', fn () => $this->createdBy->name),
            'approved_by'        => $this->approved_by,
            'approver_name'      => $this->whenLoaded('approvedBy', fn () => $this->approvedBy->name),
            'approved_at'        => $this->approved_at,
            'rejected_by'        => $this->rejected_by,
            'rejector_name'      => $this->whenLoaded('rejectedBy', fn () => $this->rejectedBy->name),
            'rejected_at'        => $this->rejected_at,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
