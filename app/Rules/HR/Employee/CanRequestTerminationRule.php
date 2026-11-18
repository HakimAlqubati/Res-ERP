<?php

declare(strict_types=1);

namespace App\Rules\HR\Employee;

use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * Ensures an employee can legally have a new service termination request created.
 * Supports multi-cycle employment (Terminate -> Rehire -> Terminate) by scoping
 * approved termination checks to the employee's current employment_cycle while
 * preserving all historical approved records intact.
 */
class CanRequestTerminationRule implements ValidationRule
{
    public function __construct(
        protected readonly ?Employee $employee = null
    ) {}

    /**
     * One-liner facade for services and observers.
     *
     * @throws ValidationException
     */
    public static function check(Employee $employee): void
    {
        $errors = [];

        (new static($employee))->validate(
            attribute: 'employee_id',
            value: $employee->id,
            fail: static function (string $message) use (&$errors): void {
                $errors[] = $message;
            }
        );

        if (! empty($errors)) {
            throw ValidationException::withMessages(['employee_id' => $errors]);
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $employee = $this->employee ?? ($value ? Employee::find($value) : null);

        if (! $employee) {
            return;
        }

        if (! (bool) $employee->active) {
            $fail('Cannot create a termination request for an inactive employee.');
            return;
        }

        $currentCycle = (int) ($employee->employment_cycle ?? 1);

        $hasActiveTermination = EmployeeServiceTermination::where('employee_id', $employee->id)
            ->where(function ($query) use ($currentCycle) {
                $query->where('status', EmployeeServiceTermination::STATUS_PENDING)
                    ->orWhere(function ($sub) use ($currentCycle) {
                        $sub->where('employment_cycle', $currentCycle)
                            ->where('status', EmployeeServiceTermination::STATUS_APPROVED);
                    });
            })
            ->exists();

        if ($hasActiveTermination) {
            $fail('Employee already has an active termination request.');
        }
    }
}
