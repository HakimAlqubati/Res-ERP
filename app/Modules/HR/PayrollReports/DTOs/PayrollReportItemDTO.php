<?php

namespace App\Modules\HR\PayrollReports\DTOs;

use App\Models\Payroll;

class PayrollReportItemDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $employeeId,
        public readonly string $employeeName,
        public readonly ?string $employeeCode,
        public readonly ?int $branchId,
        public readonly ?string $branchName,
        public readonly int $year,
        public readonly int $month,
        public readonly float $baseSalary,
        public readonly float $totalAllowances,
        public readonly float $totalBonus,
        public readonly float $totalOvertime,
        public readonly float $totalDeductions,
        public readonly float $totalAdvances,
        public readonly float $totalPenalties,
        public readonly float $grossSalary,
        public readonly float $netSalary,
        public readonly string $status,
        public readonly ?string $payDate,
    ) {
    }

    /**
     * Map from Eloquent Model
     */
    public static function fromModel(Payroll $payroll): self
    {
        return new self(
            id: $payroll->id,
            employeeId: $payroll->employee_id,
            employeeName: $payroll->employee->name ?? $payroll->name ?? 'Unknown',
            employeeCode: $payroll->employee->code ?? null,
            branchId: $payroll->branch_id,
            branchName: $payroll->branch->name ?? 'N/A',
            year: $payroll->year,
            month: $payroll->month,
            baseSalary: (float) $payroll->base_salary,
            totalAllowances: (float) $payroll->total_allowances,
            totalBonus: (float) $payroll->total_bonus,
            totalOvertime: (float) $payroll->overtime_amount,
            totalDeductions: (float) $payroll->total_deductions,
            totalAdvances: (float) $payroll->total_advances,
            totalPenalties: (float) $payroll->total_penalties,
            grossSalary: (float) $payroll->gross_salary,
            netSalary: (float) $payroll->net_salary,
            status: $payroll->status,
            payDate: $payroll->pay_date ? $payroll->pay_date->format('Y-m-d') : null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employeeId,
            'employee_name' => $this->employeeName,
            'employee_code' => $this->employeeCode,
            'branch_id' => $this->branchId,
            'branch_name' => $this->branchName,
            'year' => $this->year,
            'month' => $this->month,
            'base_salary' => $this->baseSalary,
            'total_allowances' => $this->totalAllowances,
            'total_bonus' => $this->totalBonus,
            'total_overtime' => $this->totalOvertime,
            'total_deductions' => $this->totalDeductions,
            'total_advances' => $this->totalAdvances,
            'total_penalties' => $this->totalPenalties,
            'gross_salary' => $this->grossSalary,
            'net_salary' => $this->netSalary,
            'status' => $this->status,
            'pay_date' => $this->payDate,
        ];
    }
}
