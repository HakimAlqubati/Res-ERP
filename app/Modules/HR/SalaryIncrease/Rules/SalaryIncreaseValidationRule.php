<?php

declare(strict_types=1);

namespace App\Modules\HR\SalaryIncrease\Rules;

use App\Models\Employee;
use App\Models\SalaryIncreaseRequest;
use Illuminate\Validation\ValidationException;

/**
 * Business rule checks for SalaryIncreaseRequest creation, updates, and state transitions.
 */
final class SalaryIncreaseValidationRule
{
    /**
     * @throws ValidationException
     */
    public function assertCanCreateOrUpdate(
        Employee $employee,
        array $calculatedAmounts,
        ?int $ignoreRequestId = null,
        bool $allowMultiplePending = false,
        bool $hasExistingPending = false
    ): void {
        if (! (bool) $employee->active) {
            throw ValidationException::withMessages([
                'employee_id' => ['Cannot create a salary increase request for an inactive employee.'],
            ]);
        }

        if (($calculatedAmounts['increase_amount'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'new_salary' => ['The new salary must be greater than the employee\'s current salary.'],
            ]);
        }

        if (! $allowMultiplePending && $hasExistingPending) {
            throw ValidationException::withMessages([
                'employee_id' => ['This employee already has a pending salary increase request.'],
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function assertCanTransition(SalaryIncreaseRequest $request, string $targetStatus): void
    {
        if (! $request->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition salary increase request #{$request->id} from '{$request->status}' to '{$targetStatus}'."],
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function assertIsPending(SalaryIncreaseRequest $request): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Only pending salary increase requests can be modified or deleted.'],
            ]);
        }
    }
}
