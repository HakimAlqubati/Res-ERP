<?php

namespace App\Modules\HR\Employee\Services;

use App\Models\Employee;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PassportValidationService
{
    /**
     * Validate that the employee's passport is unique per nationality.
     * This provides a "smart" validation by ignoring case, spaces, and dashes.
     *
     * @param Employee $employee
     * @throws ValidationException
     */
    public function validateUniquePassportPerNationality(Employee $employee): void
    {
        // If passport_no or nationality is not provided, no need to validate this rule.
        if (empty($employee->passport_no) || empty($employee->nationality)) {
            return;
        }

        // Normalize passport: remove spaces, dashes, and convert to uppercase for smart comparison
        $normalizedPassport = Str::upper(preg_replace('/[\s\-]+/', '', $employee->passport_no));

        // Check against existing employees (including soft deleted to prevent future conflicts if restored)
        $query = Employee::withTrashed()
            ->where('nationality', $employee->nationality)
            ->where(function ($q) use ($normalizedPassport, $employee) {
                // MySQL specific string manipulation to match the normalized passport
                $q->whereRaw("REPLACE(REPLACE(UPPER(passport_no), ' ', ''), '-', '') = ?", [$normalizedPassport])
                  ->orWhere('passport_no', $employee->passport_no); // Fallback for exact match
            });

        // Exclude the current employee when updating
        if ($employee->exists) {
            $query->where('id', '!=', $employee->id);
        }

        // If a duplicate is found, throw a validation exception
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'passport_no' => __('This passport number is already registered for an employee with the same nationality.')
            ]);
        }
    }
}
