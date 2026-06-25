<?php

namespace App\Modules\HR\Employee\Services;

use App\Models\Employee;
use Illuminate\Validation\ValidationException;
use App\Rules\HR\Employee\UniquePassportPerNationalityRule;

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
        if (empty($employee->passport_no) || empty($employee->nationality)) {
            return;
        }

        $this->rule($employee->id, $employee->nationality)->validate(
            'passport_no',
            $employee->passport_no,
            function (string $message) {
                throw ValidationException::withMessages([
                    'passport_no' => [$message]
                ]);
            }
        );
    }

    /**
     * Get the unique passport per nationality validation rule.
     *
     * @param int|null $ignoreEmployeeId
     * @param string|null $nationality
     * @return UniquePassportPerNationalityRule
     */
    public function rule(?int $ignoreEmployeeId = null, ?string $nationality = null): UniquePassportPerNationalityRule
    {
        return new UniquePassportPerNationalityRule($ignoreEmployeeId, $nationality);
    }
}
