<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
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
            'name' => $this->name,
            'employee' => [
                'id' => $this->employee_id,
                'name' => $this->whenLoaded('employee', fn() => $this->employee->name),
            ],
            'branch' => [
                'id' => $this->branch_id,
                'name' => $this->whenLoaded('branch', fn() => $this->branch->name),
            ],
            'period' => [
                'year' => $this->year,
                'month' => $this->month,
                'start_date' => $this->period_start_date,
                'end_date' => $this->period_end_date,
            ],
            'financial' => [
                'base_salary' => $this->base_salary,
                'total_allowances' => $this->total_allowances,
                'total_bonus' => $this->total_bonus,
                'overtime_amount' => $this->overtime_amount,
                'total_deductions' => $this->total_deductions,
                'total_advances' => $this->total_advances,
                'total_penalties' => $this->total_penalties,
                'total_insurance' => $this->total_insurance,
                'employer_share' => $this->employer_share,
                'employee_share' => $this->employee_share,
                'taxes_amount' => $this->taxes_amount,
                'other_deductions' => $this->other_deductions,
                'gross_salary' => $this->gross_salary,
                'net_salary' => $this->net_salary,
                'currency' => $this->currency,
            ],
            'status' => $this->status, // Use original status
            'status_label' => \App\Models\Payroll::statuses()[$this->status] ?? $this->status,
            'dates' => [
                'pay_date' => $this->pay_date,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'notes' => $this->notes,
        ];
    }
}
