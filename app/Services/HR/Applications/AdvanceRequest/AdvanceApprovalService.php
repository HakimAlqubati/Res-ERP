<?php

namespace App\Services\HR\Applications\AdvanceRequest;

use App\Models\AdvanceRequest;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeApplicationV2;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Handles the full lifecycle of advancing an advance-request application
 * through the "approved" state:
 *
 *  1. Validate that all required data is present.
 *  2. Prevent duplicate installment generation.
 *  3. Generate the installment schedule.
 *  4. Recompute advance-request aggregate columns.
 *  5. Create the corresponding financial transaction.
 *
 * This service is intentionally infrastructure-agnostic — it does NOT know
 * whether the approval originated from Filament, the API, or a CLI command.
 * Callers are responsible for wrapping calls in a DB transaction if needed.
 */
class AdvanceApprovalService
{
    // =========================================================================
    //  Public API
    // =========================================================================

    /**
     * Process all side-effects that must occur when an advance-request
     * application is approved.
     *
     * @throws \RuntimeException if required data is missing.
     */
    public function process(EmployeeApplicationV2 $application): void
    {
        $advanceRequest = $application->advanceRequest;

        $this->guardRequiredData($application, $advanceRequest);

        if ($this->installmentsAlreadyExist($application->id)) {
            Log::info('[AdvanceApprovalService] Installments already exist — skipping.', [
                'application_id' => $application->id,
            ]);
            return;
        }

        $this->generateInstallments($application, $advanceRequest);
        $this->recomputeTotals($advanceRequest, $application->id);
        $advanceRequest->createFinancialTransaction();
    }

    // =========================================================================
    //  Private Steps
    // =========================================================================

    /**
     * Ensure all data required to build an installment schedule is present.
     *
     * @throws \RuntimeException
     */
    private function guardRequiredData(EmployeeApplicationV2 $application, ?AdvanceRequest $advanceRequest): void
    {
        if (
            ! $advanceRequest ||
            ! $application->employee_id ||
            ! $advanceRequest->advance_amount ||
            ! $advanceRequest->number_of_months_of_deduction ||
            ! $advanceRequest->deduction_starts_from ||
            ! $advanceRequest->finance_approved_at
        ) {
            throw new \RuntimeException(
                "Advance application #{$application->id}: missing required data or financial approval to generate installments."
            );
        }
    }

    /**
     * Check whether installments have already been created for this application.
     */
    private function installmentsAlreadyExist(int $applicationId): bool
    {
        return EmployeeAdvanceInstallment::where('application_id', $applicationId)->exists();
    }

    /**
     * Build and persist the full installment schedule.
     */
    private function generateInstallments(EmployeeApplicationV2 $application, AdvanceRequest $advanceRequest): void
    {
        $startMonth = Carbon::parse($advanceRequest->deduction_starts_from)
            ->startOfMonth()
            ->toDateString();

        AdvanceRequest::createInstallments(
            employeeId: $application->employee_id,
            totalAmount: $advanceRequest->advance_amount,
            monthlyDeductionAmount: $advanceRequest->monthly_deduction_amount,
            numberOfMonths: $advanceRequest->number_of_months_of_deduction,
            startMonth: $startMonth,
            applicationId: $application->id,
        );
    }

    /**
     * Refresh aggregate columns on the advance request record
     * (remaining_total, paid_installments, deduction_ends_at).
     *
     * Delegates to the model method `recomputeTotals()` if it exists,
     * otherwise performs the calculation inline.
     */
    private function recomputeTotals(AdvanceRequest $advanceRequest, int $applicationId): void
    {
        if (method_exists($advanceRequest, 'recomputeTotals')) {
            $advanceRequest->refresh()->recomputeTotals();
            return;
        }

        $base = EmployeeAdvanceInstallment::where('application_id', $applicationId);

        $totalAmount  = (float) (clone $base)->sum('installment_amount');
        $paidAmount   = (float) (clone $base)->where('is_paid', true)->sum('installment_amount');
        $paidCount    =         (clone $base)->where('is_paid', true)->count();
        $lastDueDate  =         (clone $base)->max('due_date');

        $advanceRequest->remaining_total   = round($totalAmount - $paidAmount, 2);
        $advanceRequest->paid_installments = $paidCount;

        if ($lastDueDate) {
            $advanceRequest->deduction_ends_at = $lastDueDate;
        }

        $advanceRequest->saveQuietly();
    }
}
