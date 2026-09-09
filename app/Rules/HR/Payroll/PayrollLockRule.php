<?php

namespace App\Rules\HR\Payroll;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\HR\Payroll\PayrollLockGuard;
use Illuminate\Validation\ValidationException;

/**
 * PayrollLockRule
 *
 * Validates that the operation date/period does not belong to an already finalized payroll run.
 */
class PayrollLockRule implements ValidationRule
{
    public function __construct(
        protected ?int $employeeId,
        protected ?int $year,
        protected ?int $month,
    ) {}

    /**
     * One-liner façade for observers and services.
     *
     * @throws ValidationException
     */
    public static function check(?int $employeeId, ?int $year, ?int $month, string $attribute = 'date'): void
    {
        if (! $employeeId || ! $year || ! $month) {
            return;
        }

        app(PayrollLockGuard::class)->checkLock((int) $employeeId, (int) $year, (int) $month, $attribute);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->employeeId || ! $this->year || ! $this->month) {
            return;
        }

        try {
            app(PayrollLockGuard::class)->checkLock((int) $this->employeeId, (int) $this->year, (int) $this->month, $attribute);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $fail($message);
                }
            }
        }
    }
}
