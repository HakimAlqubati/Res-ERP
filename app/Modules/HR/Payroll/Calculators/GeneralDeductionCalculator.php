<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\Deduction;
use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حساب الخصومات العامة (ضرائب، تأمينات، إلخ)
 */
class GeneralDeductionCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب الخصومات العامة للموظف
     */
    public function calculate(CalculationContext $context, float $netSalary): array
    {
        $employee = $context->employee;

        // جلب أنواع الخصومات العامة النشطة
        $generalDeductionTypes = Deduction::where('is_specific', 0)
            ->where('active', 1)
            ->select(
                'name',
                'is_percentage',
                'amount',
                'percentage',
                'id',
                'condition_applied_v2',
                'nationalities_applied',
                'less_salary_to_apply',
                'has_brackets',
                'applied_by',
                'employer_percentage',
                'employer_amount'
            )
            ->with('brackets')
            ->get();

        $deductions = [];

        foreach ($generalDeductionTypes as $deductionType) {
            // تطبيق على الكل
            if ($deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_ALL) {
                $deductions[] = $deductionType;
            }

            // تطبيق على المواطنين مع brackets
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
                $employee->is_citizen &&
                $deductionType->has_brackets == 1
            ) {
                $deductions[] = $deductionType;
            }

            // تطبيق على المواطنين
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
                $employee->is_citizen
            ) {
                $deductions[] = $deductionType;
            }

            // تطبيق على المواطنين والأجانب مع رخصة عمل
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE_AND_FOREIGN_HAS_PASS &&
                ($employee->is_citizen || ($employee->has_employee_pass))
            ) {
                $deductions[] = $deductionType;
            }
        }

        return $this->calculateDeductions($deductions, $netSalary);
    }

    /**
     * حساب مبالغ الخصومات
     */
    protected function calculateDeductions(array $deductions, float $basicSalary): array
    {
        $finalDeductions = [];
        $totalDeductions = 0.0;

        foreach ($deductions as $deduction) {
            $deductionAmount = 0.0;
            $employerAmount = 0.0;
            $effectivePercentage = null;
            $notes = null;
            $appliedBrackets = null;

            // حساب خصم الموظف
            if (isset($deduction->has_brackets) && $deduction->has_brackets && $deduction->brackets->isNotEmpty()) {
                // حساب تصاعدي (ضريبة)
                $taxResult = $deduction->calculateTax($basicSalary);
                $deductionAmount = $taxResult['monthly_tax'] ?? 0;
                $effectivePercentage = $taxResult['effective_percentage'] ?? null;
                $notes = $taxResult['notes'] ?? null;
                $appliedBrackets = $taxResult['applied_brackets'] ?? null;
            } elseif ($deduction->is_percentage) {
                // نسبة مئوية
                $salaryBase = max(0, $basicSalary);
                $deductionAmount = ($salaryBase * $deduction->percentage) / 100;
                $effectivePercentage = $deduction->percentage;
                $notes = sprintf(
                    "Flat rate deduction: %.2f%% of %.2f = %.2f",
                    $deduction->percentage,
                    $salaryBase,
                    $deductionAmount
                );
            } else {
                // مبلغ ثابت
                $deductionAmount = (float) $deduction->amount;
                $notes = sprintf("Fixed amount deduction: %.2f", $deductionAmount);
            }

            // حساب مساهمة صاحب العمل
            if ($deduction->employer_percentage > 0) {
                $salaryBase = max(0, $basicSalary);
                $employerAmount = ($salaryBase * $deduction->employer_percentage) / 100;
            } elseif ($deduction->employer_amount > 0) {
                $employerAmount = (float) $deduction->employer_amount;
            }

            $totalDeductions += $deductionAmount;

            $deductionArray = $deduction->toArray();

            $finalDeductions[] = [
                'id' => $deductionArray['id'],
                'name' => $deductionArray['name'],
                'deduction_amount' => $this->round($deductionAmount),
                'employer_deduction_amount' => $this->round($employerAmount),
                'is_percentage' => $deductionArray['is_percentage'],
                'amount_value' => $deductionArray['amount'],
                'percentage_value' => $deductionArray['percentage'],
                'applied_by' => $deductionArray['applied_by'],
                'has_brackets' => $deductionArray['has_brackets'] ?? false,
                'effective_percentage' => $effectivePercentage,
                'notes' => $notes,
                'applied_brackets' => $appliedBrackets,
            ];
        }

        $finalDeductions['result'] = $totalDeductions;

        return $finalDeductions;
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
