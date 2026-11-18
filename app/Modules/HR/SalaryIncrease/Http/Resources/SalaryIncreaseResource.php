<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryIncreaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'employee_id'         => $this->employee_id,
            'employee'            => $this->whenLoaded('employee', fn () => [
                'id'          => $this->employee->id,
                'name'        => $this->employee->name,
                'employee_no' => $this->employee->employee_no,
                'salary'      => (float) ($this->employee->salary ?? 0),
            ]),
            'branch_id'           => $this->branch_id,
            'branch'              => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'old_salary'          => (float) $this->old_salary,
            'increase_type'       => $this->increase_type,
            'increase_amount'     => (float) $this->increase_amount,
            'increase_percentage' => $this->increase_percentage !== null ? (float) $this->increase_percentage : null,
            'new_salary'          => (float) $this->new_salary,
            'effective_date'      => $this->effective_date?->toDateString(),
            'notes'               => $this->notes,
            'status'              => $this->status,
            'status_label'        => $this->statusLabel(),
            'rejection_reason'    => $this->rejection_reason,
            'created_by'          => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'approved_by'         => $this->whenLoaded('approvedBy', fn () => $this->approvedBy?->name),
            'approved_at'         => $this->approved_at?->toDateTimeString(),
            'rejected_by'         => $this->whenLoaded('rejectedBy', fn () => $this->rejectedBy?->name),
            'rejected_at'         => $this->rejected_at?->toDateTimeString(),
            'created_at'          => $this->created_at?->toDateTimeString(),
        ];
    }
}
