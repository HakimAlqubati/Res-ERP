<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeRewardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'employee_id'     => $this->employee_id,
            'employee_name'   => $this->employee?->name,
            'incentive_id'    => $this->incentive_id,
            'reward_type'     => $this->rewardType?->name,
            'reward_amount'   => $this->reward_amount,
            'reason'          => $this->reason,
            'month'           => $this->month,
            'year'            => $this->year,
            'date'            => $this->date?->format('Y-m-d'),
            'status'          => $this->status,
            'created_by'      => $this->created_by,
            'creator_name'    => $this->creator?->name,
            'approved_by'     => $this->approved_by,
            'approver_name'   => $this->approver?->name,
            'approved_at'     => $this->approved_at?->toDateTimeString(),
            'rejected_by'     => $this->rejected_by,
            'rejector_name'   => $this->rejector?->name,
            'rejected_at'     => $this->rejected_at?->toDateTimeString(),
            'rejected_reason' => $this->rejected_reason,
            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),
        ];
    }
}
