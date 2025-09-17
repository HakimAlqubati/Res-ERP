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
            'applicationType'   => $this->application_type_name,
            'applicationDate'   => $this->application_date,
            'status'            => $this->status,
            'notes'             => $this->notes,

            'leaveRequest'      => $this->leaveRequest,
            'advanceRequest'    => $this->advanceRequest,
            'createdAt'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
