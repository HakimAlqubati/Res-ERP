<?php

namespace App\Rules\HR\Employee;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Prevents approving a service termination request
 * when the termination date is set in the future.
 */
class NoFutureTerminationApprovalRule implements ValidationRule
{
    /**
     * @param string|Carbon|null $terminationDate  The termination date to validate against.
     */
    public function __construct(protected readonly mixed $terminationDate) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->terminationDate) {
            return;
        }

        if (Carbon::parse($this->terminationDate)->isFuture()) {
            $fail('Cannot approve a termination request with a future termination date.');
        }
    }
}
