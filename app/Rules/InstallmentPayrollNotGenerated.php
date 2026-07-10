<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class InstallmentPayrollNotGenerated implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $installment = \App\Models\EmployeeAdvanceInstallment::find($value);

        if ($installment) {
            $payrollExists = \App\Models\Payroll::where('employee_id', $installment->employee_id)
                ->where('year', $installment->year)
                ->where('month', $installment->month)
                ->exists();

            if ($payrollExists) {
                $fail(__('Cannot defer this installment because the payroll for this month has already been generated.'));
            }
        }
    }
}
