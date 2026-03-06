<?php

namespace App\Rules\HR\Applications;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that all advance-request deduction fields are internally consistent.
 *
 * Business rules (must match Filament advanceRequestForm logic exactly):
 *
 *  1. advance_amount > 0
 *  2. monthly_deduction_amount > 0
 *  3. monthly_deduction_amount <= advance_amount
 *  4. number_of_months_of_deduction == ceil(advance_amount / monthly_deduction_amount)
 *  5. deduction_ends_at == deduction_starts_from
 *                           + (number_of_months_of_deduction - 1) months
 *                           → end of that month  (Y-m-d)
 */
class AdvanceRequestConsistencyRule implements ValidationRule, DataAwareRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /** Allowed date-drift in days between computed and provided deduction_ends_at. */
    private const ENDS_AT_TOLERANCE_DAYS = 0;

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Entry-point called by Laravel's validator.
     * Attach this rule to any one of the advance-request keys (e.g. 'advance_request').
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // ── Pull & cast fields ────────────────────────────────────────────────
        $details = is_array($value) ? $value : ($this->data['advance_request'] ?? []);

        $advanceAmount       = (float) ($details['detail_advance_amount']               ?? 0);
        $monthlyDeduction    = (float) ($details['detail_monthly_deduction_amount']     ?? 0);
        $numberOfMonths      = (int)   ($details['detail_number_of_months_of_deduction'] ?? 0);
        $startsFrom          =          $details['detail_deduction_starts_from']         ?? null;
        $endsAt              =          $details['detail_deduction_ends_at']             ?? null;

        // ── Rule 1 & 2: positivity ────────────────────────────────────────────
        if ($advanceAmount <= 0) {
            $fail('Advance amount must be greater than zero.');
            return;
        }

        if ($monthlyDeduction <= 0) {
            $fail('Monthly deduction amount must be greater than zero.');
            return;
        }

        // ── Rule 3: deduction cannot exceed total amount ───────────────────────
        if ($monthlyDeduction > $advanceAmount) {
            $fail("Monthly deduction ({$monthlyDeduction}) cannot exceed the advance amount ({$advanceAmount}).");
            return;
        }

        // ── Rule 4: number of months consistency ──────────────────────────────
        $expectedMonths = (int) ceil($advanceAmount / $monthlyDeduction);

        if ($numberOfMonths !== $expectedMonths) {
            $fail("Number of months should be {$expectedMonths} (not {$numberOfMonths}) based on {$advanceAmount} ÷ {$monthlyDeduction}.");
            return;
        }

        // ── Rule 5: deduction_ends_at consistency ─────────────────────────────
        if (!$startsFrom || !$endsAt) {
            $fail('Deduction start date and end date are required.');
            return;
        }

        try {
            $computedEndsAt = Carbon::parse($startsFrom)
                ->startOfMonth()
                ->addMonths($expectedMonths - 1)
                ->endOfMonth()
                ->toDateString();

            $providedEndsAt = Carbon::parse($endsAt)->toDateString();

            if ($computedEndsAt !== $providedEndsAt) {
                $fail("Deduction end date should be {$computedEndsAt}, not {$providedEndsAt}.");
            }
        } catch (\Exception) {
            $fail('One or more deduction dates are invalid.');
        }
    }
}
