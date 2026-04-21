<?php

namespace App\Modules\HR\AdvanceWages\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdvanceWageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'employee_id'         => $this->employee_id,
            'employee_name'       => $this->whenLoaded('employee', fn () => $this->employee->name),
            'branch_id'           => $this->branch_id,
            'date'                => $this->date?->toDateString(),
            'year'                => $this->year,
            'month'               => $this->month,
            'amount'              => (float) $this->amount,
            'payment_method'      => $this->payment_method,
            'bank_account_number' => $this->bank_account_number,
            'transaction_number'  => $this->transaction_number,
            'status'              => $this->status,
            'reason'              => $this->reason,
            'notes'               => $this->notes,
            'settled_payroll_id'  => $this->settled_payroll_id,
            'settled_payrollName' => $this->whenLoaded('settledPayroll', fn () => $this->settledPayroll->name),
            'settled_at'          => $this->settled_at,
            'created_by'          => $this->created_by,
            'creator_name'        => $this->whenLoaded('creator', fn () => $this->creator->name),
            'approved_by'         => $this->approved_by,
            'approver_name'       => $this->whenLoaded('approver', fn () => $this->approver->name),
            'approved_at'         => $this->approved_at,
            'created_at'          => $this->created_at,
        ];
    }
}
