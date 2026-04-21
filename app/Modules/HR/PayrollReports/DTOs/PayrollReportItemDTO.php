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

    /**
     * Map from aggregated DB row (usually a generic object or an Eloquent model acting as a plain object)
     */
    public static function fromAggregatedRow(object $row): self
    {
        $netSalary = (float) $row->total_additions - (float) $row->total_deductions_all;
        if ($netSalary < 0) {
            $netSalary = 0;
        }

        // Gross salary sum of all additions except maybe specifics, but conventionally total_additions is gross.
        // If they need strict Base + Allowances + Bonus + Overtime:
        $grossSalary = (float) $row->total_additions;

        return new self(
            id: (int) $row->payroll_id,
            employeeId: (int) $row->employee_id,
            employeeName: $row->employee_name ?? 'Unknown',
            employeeCode: $row->employee_code,
            branchId: $row->branch_id ? (int) $row->branch_id : null,
            branchName: $row->branch_name ?? 'N/A',
            year: (int) $row->year,
            month: (int) $row->month,
            baseSalary: (float) $row->calculated_base_salary,
            totalAllowances: (float) $row->calculated_allowances,
            totalBonus: (float) $row->calculated_bonus,
            totalOvertime: (float) $row->calculated_overtime,
            totalDeductions: (float) $row->calculated_deductions,
            totalAdvances: (float) $row->calculated_advances,
            totalPenalties: (float) $row->calculated_penalties,
            grossSalary: $grossSalary,
            netSalary: $netSalary,
            status: $row->status ?? 'unknown',
            payDate: $row->pay_date ? \Carbon\Carbon::parse($row->pay_date)->format('Y-m-d') : null,
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
