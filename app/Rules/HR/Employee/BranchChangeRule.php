<?php

namespace App\Rules\HR\Employee;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\EmployeeBranchLog;
use Carbon\Carbon;

/**
 * Class BranchChangeRule
 * 
 * Professional validation rule for branch changes.
 * Handles duplicate branch prevention and period overlap detection.
 */
class BranchChangeRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param int|null $currentBranchId
     * @param int|null $employeeId
     * @param string|null $startAt
     * @param string|null $endAt
     */
    public function __construct(
        protected ?int $currentBranchId,
        protected ?int $employeeId = null,
        protected ?string $startAt = null,
        protected ?string $endAt = null
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
        if (!$value) {
            return;
        }

        // 1. Prevent selecting the current branch as the "new" branch
        if ($this->currentBranchId && (int) $value === (int) $this->currentBranchId) {
            $fail(__('lang.cannot_select_current_branch'));
            return;
        }

        // 2. Prevent overlapping periods
        if ($this->employeeId && $this->startAt) {
            $this->checkOverlap($fail);
        }
    }

    /**
     * Checks if the proposed branch assignment period overlaps with existing records.
     *
     * @param Closure $fail
     * @return void
     */
    protected function checkOverlap(Closure $fail): void
    {
        try {
            $newStart = Carbon::parse($this->startAt);
            $newEnd   = $this->endAt ? Carbon::parse($this->endAt) : null;

            if ($newEnd && $newEnd->lt($newStart)) {
                $fail(__('lang.end_date_must_be_after_start_date'));
                return;
            }

            // Check against the currently active log (which will be closed by the action)
            $activeLog = EmployeeBranchLog::where('employee_id', $this->employeeId)
                ->whereNull('end_at')
                ->first();

            if ($activeLog) {
                // If the new start date is earlier than the current branch's start date, it's an overlap error
                if ($newStart->lt(Carbon::parse($activeLog->start_at))) {
                    $fail(__('lang.start_date_before_current_branch_start', [
                        'date' => Carbon::parse($activeLog->start_at)->toDateString()
                    ]));
                    return;
                }
            }

            // General overlap check against all OTHER logs
            // Condition for overlap: R.start < N.end AND N.start < R.end
            $hasOverlap = EmployeeBranchLog::where('employee_id', $this->employeeId)
                ->when($activeLog, fn($q) => $q->where('id', '!=', $activeLog->id))
                ->where(function ($query) use ($newStart, $newEnd) {
                    $query->where('start_at', '<', $newEnd ?? '9999-12-31')
                          ->where(function ($q) use ($newStart) {
                              $q->whereNull('end_at')->orWhere('end_at', '>', $newStart->toDateString());
                          });
                })
                ->exists();

            if ($hasOverlap) {
                $fail(__('lang.branch_period_overlap'));
            }
        } catch (\Exception $e) {
            // Silently fail validation if date parsing fails (other validators will catch it)
            return;
        }
    }
}
