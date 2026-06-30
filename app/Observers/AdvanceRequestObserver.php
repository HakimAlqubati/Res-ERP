<?php

namespace App\Observers;

use App\Models\AdvanceRequest;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeApplicationV2;
use Illuminate\Validation\ValidationException;

/**
 * Observer for AdvanceRequest model.
 *
 * Validates business rules before creating a new advance request:
 *  - Employee must not already have an advance request in the same month.
 *  - Employee must not have any outstanding (unpaid) installments from previous advances.
 */
class AdvanceRequestObserver
{
    /**
     * @throws ValidationException
     */
    public function creating(AdvanceRequest $advance): void
    {
        $this->ensureNoDuplicateInMonth($advance);
        $this->ensureNoOutstandingInstallments($advance);
    }

    // =========================================================================
    //  Validation Rules
    // =========================================================================

    /**
     * Reject if the employee already has an advance request in the same month.
     *
     * @throws ValidationException
     */
    private function ensureNoDuplicateInMonth(AdvanceRequest $advance): void
    {
        $date = $advance->date
            ? \Carbon\Carbon::parse($advance->date)
            : now();

        $exists = AdvanceRequest::where('employee_id', $advance->employee_id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->whereHas('application', fn($query) => $query->where('status', '!=', EmployeeApplicationV2::STATUS_REJECTED))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'advance_request' => [
                    __('lang.advance_already_exists_in_month', [
                        'month' => $date->translatedFormat('F Y'),
                    ]),
                ],
            ]);
        }
    }

    /**
     * Reject if the employee still has scheduled (unpaid) installments
     * from a previous advance.
     *
     * @throws ValidationException
     */
    private function ensureNoOutstandingInstallments(AdvanceRequest $advance): void
    {
        $hasScheduled = EmployeeAdvanceInstallment::where('employee_id', $advance->employee_id)
            ->where('is_paid', false)
            ->exists();

        if ($hasScheduled) {
            throw ValidationException::withMessages([
                'advance_request' => [
                    __('lang.advance_has_outstanding_installments'),
                ],
            ]);
        }
    }
}
