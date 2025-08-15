<?php 
namespace App\Contracts\HR;

use App\Models\Employee;
use App\Services\HR\Payroll\SalaryMutableComponents;

interface SalaryPolicyHookInterface
{
    /**
     * Called before computing rates (can enrich $context).
     */
    public function beforeRates(Employee $employee, array &$context): void;

    /**
     * Adjust overtime amount if needed (caps/multipliers).
     */
    public function adjustOvertime(Employee $employee, array $context, float $overtimeAmount): float;

    /**
     * Adjust absence/late deductions (caps/floors).
     * Return [absenceDeduction, lateDeduction].
     */
    public function adjustDeductions(Employee $employee, array $context, float $absenceDeduction, float $lateDeduction): array;

    /**
     * After totals computed; return final net salary (allow taxes, insurances…).
     * You may also modify $mut (gross/deductions/overtime…) to reflect changes externally if you wish.
     */
    public function afterTotals(
        Employee $employee,
        array $context,
        float $baseSalary,
        float $grossSalary,
        float $totalDeductions,
        float $currentNet,
        SalaryMutableComponents $mut
    ): float;

    /**
     * Optional extra line-items to be persisted as transactions.
     * Each item: ['type'=>..,'sub_type'=>..(opt),'amount'=>..,'operation'=>'+|-', 'description'=>..]
     */
    public function extraTransactions(Employee $employee, array $context): array;
}