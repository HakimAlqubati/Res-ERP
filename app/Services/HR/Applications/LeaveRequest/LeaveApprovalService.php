<?php

namespace App\Services\HR\Applications\LeaveRequest;

use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use App\Rules\HR\Applications\MaxLeavePerMonthRule;
use Illuminate\Support\Facades\Log;

/**
 * Class LeaveApprovalService
 *
 * Handles all side-effects that must occur when a leave-request application
 * transitions to a new status (approved / pending / rejected / reverted).
 *
 * Responsibilities:
 *  1. Validate that the leave request details are present.
 *  2. Locate the correct leave balance record via LeaveBalanceResolver.
 *  3. Increment/decrement used_days and pending_days accordingly.
 *
 * This service is infrastructure-agnostic — it does NOT know whether
 * the status change came from Filament, the API, or a CLI command.
 * Callers are responsible for wrapping calls in a DB transaction.
 *
 * @package App\Services\HR\Applications\LeaveRequest
 */
class LeaveApprovalService
{
    public function __construct(
        private readonly LeaveBalanceResolver $resolver,
    ) {}

    // =========================================================================
    //  Public API
    // =========================================================================

    /**
     * Process side-effects when a leave request is approved.
     *
     * - Moves days from pending_days → used_days.
     * - If no pending record existed, increments used_days directly.
     *
     * @throws \RuntimeException if leave request details are missing or balance not found.
     */
    public function onApproved(EmployeeApplicationV2 $application): void
    {
        $leaveRequest = $this->guardLeaveRequest($application);

        MaxLeavePerMonthRule::check($leaveRequest, MaxLeavePerMonthRule::CONTEXT_APPROVING);

        $balance = $this->guardBalance($leaveRequest, $application->id);
        $days         = (float) $leaveRequest->days_count;

        // Check if balance is sufficient
        \App\Rules\HR\Applications\SufficientLeaveBalanceRule::check($leaveRequest, $balance);

        Log::info('[LeaveApprovalService] Processing approval.', [
            'application_id' => $application->id,
            'employee_id'    => $application->employee_id,
            'days'           => $days,
            'balance_id'     => $balance->id,
        ]);

        $this->transferPendingToUsed($balance, $days);
    }

    /**
     * Process side-effects when a leave request is set to pending.
     *
     * - Increments pending_days only (does not touch used_days).
     *
     * @throws \RuntimeException
     */
    public function onPending(EmployeeApplicationV2 $application): void
    {
        $leaveRequest = $this->guardLeaveRequest($application);
        $balance      = $this->guardBalance($leaveRequest, $application->id);
        $days         = (float) $leaveRequest->days_count;

        Log::info('[LeaveApprovalService] Adding to pending.', [
            'application_id' => $application->id,
            'employee_id'    => $application->employee_id,
            'days'           => $days,
            'balance_id'     => $balance->id,
        ]);

        $balance->increment('pending_days', $days);
    }

    /**
     * Revert side-effects when an approved leave request is rejected or cancelled.
     *
     * - Decrements used_days by the leave duration.
     *
     * @throws \RuntimeException
     */
    public function onRejectedFromApproved(EmployeeApplicationV2 $application): void
    {
        $leaveRequest = $this->guardLeaveRequest($application);
        $balance      = $this->guardBalance($leaveRequest, $application->id);
        $days         = (float) $leaveRequest->days_count;

        Log::info('[LeaveApprovalService] Reverting approved leave.', [
            'application_id' => $application->id,
            'days'           => $days,
            'balance_id'     => $balance->id,
        ]);

        $balance->decrement('used_days', $days);
    }

    /**
     * Revert side-effects when an approved leave request is reverted to pending.
     *
     * - Decrements used_days and increments pending_days.
     *
     * @throws \RuntimeException
     */
    public function onRevertedToPendingFromApproved(EmployeeApplicationV2 $application): void
    {
        $leaveRequest = $this->guardLeaveRequest($application);
        $balance      = $this->guardBalance($leaveRequest, $application->id);
        $days         = (float) $leaveRequest->days_count;

        Log::info('[LeaveApprovalService] Reverting approved to pending.', [
            'application_id' => $application->id,
            'days'           => $days,
            'balance_id'     => $balance->id,
        ]);

        $usedToRemove = min($balance->used_days, $days);
        $balance->used_days = max(0, $balance->used_days - $usedToRemove);
        $balance->pending_days += $days;
        $balance->saveQuietly();
    }

    /**
     * Revert side-effects when a pending leave request is rejected or cancelled.
     *
     * - Decrements pending_days by the leave duration.
     *
     * @throws \RuntimeException
     */
    public function onRejectedFromPending(EmployeeApplicationV2 $application): void
    {
        $leaveRequest = $this->guardLeaveRequest($application);
        $balance      = $this->guardBalance($leaveRequest, $application->id);
        $days         = (float) $leaveRequest->days_count;

        Log::info('[LeaveApprovalService] Reverting pending leave.', [
            'application_id' => $application->id,
            'days'           => $days,
            'balance_id'     => $balance->id,
        ]);

        $balance->decrement('pending_days', $days);
    }

    // =========================================================================
    //  Private Steps
    // =========================================================================

    /**
     * Ensure the application has a linked leave request record.
     *
     * @throws \RuntimeException
     */
    private function guardLeaveRequest(EmployeeApplicationV2 $application): \App\Models\LeaveRequest
    {
        $leaveRequest = $application->leaveRequest;

        if (! $leaveRequest) {
            throw new \RuntimeException(
                "Leave application #{$application->id}: leaveRequest details are missing."
            );
        }

        return $leaveRequest;
    }

    /**
     * Locate the balance record; throw if not found.
     *
     * @throws \RuntimeException
     */
    private function guardBalance(\App\Models\LeaveRequest $leaveRequest, int $applicationId): LeaveBalance
    {
        $balance = $this->resolver->resolve($leaveRequest);

        if (! $balance) {
            throw new \RuntimeException(
                "Leave application #{$applicationId}: no matching LeaveBalance found "
                . "for employee {$leaveRequest->employee_id}, "
                . "leave_type {$leaveRequest->leave_type}, "
                . "year {$leaveRequest->year}."
            );
        }

        return $balance;
    }

    /**
     * Move days from pending_days to used_days atomically.
     * Clamps the pending decrement to avoid going below zero.
     */
    private function transferPendingToUsed(LeaveBalance $balance, float $days): void
    {
        $pendingToRemove = min($balance->pending_days, $days);

        $balance->pending_days = max(0, $balance->pending_days - $pendingToRemove);
        $balance->used_days   += $days;
        $balance->saveQuietly();
    }
}
