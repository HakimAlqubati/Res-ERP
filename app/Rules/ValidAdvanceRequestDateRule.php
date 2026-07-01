<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\AdvanceRequest;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeApplicationV2;
use Carbon\Carbon;

class ValidAdvanceRequestDateRule implements ValidationRule
{
    protected ?int $employeeId;
    protected ?int $ignoreAdvanceId;

    public function __construct(?int $employeeId, ?int $ignoreAdvanceId = null)
    {
        $this->employeeId = $employeeId;
        $this->ignoreAdvanceId = $ignoreAdvanceId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->employeeId) {
            return;
        }

        $date = $value ? Carbon::parse($value) : now();

        $query = AdvanceRequest::where('employee_id', $this->employeeId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->whereHas('application', fn($q) => $q->where('status', '!=', EmployeeApplicationV2::STATUS_REJECTED));

        if ($this->ignoreAdvanceId) {
            $query->where('id', '!=', $this->ignoreAdvanceId);
        }

        if ($query->exists()) {
            $fail(__('lang.advance_already_exists_in_month', [
                'month' => $date->translatedFormat('F Y'),
            ]));
            return;
        }

        $hasScheduled = EmployeeAdvanceInstallment::where('employee_id', $this->employeeId)
            ->where('is_paid', false)
            ->exists();

        if ($hasScheduled) {
            $fail(__('lang.advance_has_outstanding_installments'));
        }
    }
}
