<?php

declare(strict_types=1);

namespace App\Rules\HR\Employee;

use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * Ensures an employee has no unpaid advance installments before termination
 * request creation or approval.
 */
class EmployeeFinancialClearanceRule implements ValidationRule
{
    public function __construct(
        protected readonly ?Employee $employee = null
    ) {}

    /**
     * @throws ValidationException
     */
    public static function check(Employee $employee, string $errorField = 'financial_clearance'): void
    {
        $errors = [];

        (new static($employee))->validate(
            attribute: $errorField,
            value: $employee->id,
            fail: static function (string $message) use (&$errors): void {
                $errors[] = $message;
            }
        );

        if (! empty($errors)) {
            throw ValidationException::withMessages([$errorField => $errors]);
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $employee = $this->employee ?? ($value ? Employee::find($value) : null);

        if (! $employee) {
            return;
        }

        $unpaidBalance = (float) $employee->advancedInstallments()
            ->where('is_paid', false)
            ->sum('installment_amount');

        if ($unpaidBalance > 0) {
            $formatted = number_format($unpaidBalance, 2);
            $translated = __('lang.cannot_process_financial_clearance', ['amount' => $formatted]);

            $fail($translated !== 'lang.cannot_process_financial_clearance'
                ? $translated
                : "Cannot process financial clearance. The employee has outstanding advance installments amounting to: {$formatted}");
        }
    }
}
