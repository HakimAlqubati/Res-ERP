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
            'rejected_reason'       => $this->rejected_reason,
            'rejected_by'           => $this->rejectedBy ? [
                'id' => $this->rejectedBy->id,
                'name' => $this->rejectedBy->name,
                'rejected_at' => $this->rejected_at,
            ] : null,
            'approved_by'           => $this->approvedBy ? [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
                'approved_at' => $this->approved_at,
            ] : null,

            'leaveRequest'          => $this->leaveRequest,
            'advanceRequest'    => $this->advanceRequest,
            'missedCheckinRequest'    => $this->missedCheckinRequest,
            'missedCheckoutRequest'    => $this->missedCheckoutRequest,
            'mealRequest'           => $this->mealRequest,
            'images'            => $this->getMedia('images')->map(fn($media) => $media->getFullUrl()),
            'files'             => $this->getMedia('files')->map(fn($media) => $media->getFullUrl()),
            'createdAt'         => $this->created_at?->toDateTimeString(),
            
            'approval_steps'    => $this->approvalSteps ? $this->approvalSteps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'step_order' => $step->step_order,
                    'status' => $step->status,
                    'approver_user' => $step->approverUser?->name,
                    'approver_role' => $step->approverRole?->name,
                    'approver_employee' => $step->approverEmployee?->name,
                    'approved_at' => $step->approved_at,
                    'rejected_at' => $step->rejected_at,
                    'notes' => $step->notes,
                ];
            }) : null,
        ];
    }
}
