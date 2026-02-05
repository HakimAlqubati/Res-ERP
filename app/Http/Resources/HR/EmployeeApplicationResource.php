<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'employee'          => $this->employee?->name,
            'applicationTypeId' => $this->application_type_id,
            'branch_id'         => $this->branch_id,
            'branch_name'       => $this->branch?->name,
            'applicationType'   => $this->application_type_name,
            'applicationDate'   => $this->application_date,
            'status'            => $this->status,
            'notes'             => $this->notes,
            'rejected_reason'             => $this->rejected_reason,

            'leaveRequest'      => $this->leaveRequest,
            'advanceRequest'    => $this->advanceRequest,
            'missedCheckinRequest'    => $this->missedCheckinRequest,
            'missedCheckoutRequest'    => $this->missedCheckoutRequest,
            'createdAt'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
