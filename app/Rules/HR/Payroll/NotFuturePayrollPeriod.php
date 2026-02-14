<?php

namespace App\Rules\HR\Payroll;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class NotFuturePayrollPeriod implements ValidationRule, DataAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // We expect 'year' and 'month' in the data.
        // This rule is typically attached to the 'month' field.
        $year = $this->data['year'] ?? null;
        $month = $value; // Current attribute is 'month'

        if (!$year || !$month) {
            return;
        }

        try {
            $requestedDate = Carbon::create($year, $month, 1)->startOfMonth();
            $currentDate = Carbon::now()->startOfMonth();

            if ($requestedDate->gt($currentDate)) {
                $fail(__('Payroll creation for future months is not allowed.'));
            }
        } catch (\Exception $e) {
            $fail(__('The entered date is invalid.'));
        }
    }
}
