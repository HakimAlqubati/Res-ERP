<?php

namespace App\Rules\HR\Employee;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Employee;
use Illuminate\Support\Str;

class UniquePassportPerNationalityRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param int|null $ignoreEmployeeId
     * @param string|null $nationality
     */
    public function __construct(
        protected ?int $ignoreEmployeeId = null,
        protected ?string $nationality = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If passport_no or nationality is not provided, no need to validate this rule.
        if (empty($value) || empty($this->nationality)) {
            return;
        }

        // Normalize passport: remove spaces, dashes, and convert to uppercase for smart comparison
        $normalizedPassport = Str::upper(preg_replace('/[\s\-]+/', '', $value));

        // Check against existing employees (including soft deleted to prevent future conflicts if restored)
        $query = Employee::withTrashed()
            ->where('nationality', $this->nationality)
            ->where(function ($q) use ($normalizedPassport, $value) {
                // MySQL specific string manipulation to match the normalized passport
                $q->whereRaw("REPLACE(REPLACE(UPPER(passport_no), ' ', ''), '-', '') = ?", [$normalizedPassport])
                  ->orWhere('passport_no', $value); // Fallback for exact match
            });

        // Exclude the current employee when updating
        if ($this->ignoreEmployeeId) {
            $query->where('id', '!=', $this->ignoreEmployeeId);
        }

        // If a duplicate is found, fail the validation
        if ($query->exists()) {
            $fail(__('This passport number is already registered for an employee with the same nationality.'));
        }
    }
}
