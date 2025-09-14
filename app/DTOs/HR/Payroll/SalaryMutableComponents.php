<?php
namespace App\Services\HR\Payroll; 
final class SalaryMutableComponents
{
    public function __construct(
        public float $absenceDeduction,
        public float $lateDeduction,
        public float $overtimeAmount,
        public float $grossSalary,
        public float $totalDeductions,
    ) {}
}
