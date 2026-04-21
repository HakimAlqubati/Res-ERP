<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Resources\Json\JsonResource;

class PenaltyDeductionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'employee_id'      => $this->employee_id,
            'employee_name'    => $this->employee?->name,
            'employeeName' => $this->employee?->name,
            'deduction_id'    => $this->deduction_id,
            'deduction_name'  => $this->deduction?->name,
            'penalty_amount'  => (float) $this->penalty_amount,
            'description'     => $this->description,
            'month'           => (int) $this->month,
            'year'            => (int) $this->year,
            'deduction_type'  => $this->deduction_type,
            'status'          => $this->status,
            'status_label'    => $this->status_label,
            'percentage'      => (float) $this->percentage,
            'date'            => $this->date,
            'created_by'      => $this->creator?->name,
            'approved_by'     => $this->approver?->name,
            'rejected_by'     => $this->rejector?->name,
            'rejected_reason' => $this->rejected_reason,
            'approved_at'     => $this->approved_at,
            'rejected_at'     => $this->rejected_at,
            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),
        ];
    }
}
