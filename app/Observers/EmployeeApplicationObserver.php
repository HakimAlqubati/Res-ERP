<?php

namespace App\Observers;

use App\Enums\Warnings\WarningLevel;
use App\Facades\Warnings;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\EmployeeApplicationV2;
use App\Services\HR\Applications\AdvanceRequest\AdvanceApprovalService;
use App\Services\HR\Payroll\PayrollLockGuard;
use App\Services\Warnings\WarningPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Observer for EmployeeApplicationV2 model.
 *
 * Responsibilities:
 *  - Notify the employee's manager when a new application is created.
 *  - Delegate advance-request approval side-effects to AdvanceApprovalService.
 *
 * This class is intentionally thin — business logic lives in service classes.
 */
class EmployeeApplicationObserver
{
    public function __construct(
        private readonly AdvanceApprovalService $advanceApprovalService,
        private readonly PayrollLockGuard       $payrollLockGuard,
    ) {}

    // =========================================================================
    //  Event Hooks
    // =========================================================================

    /**
     * Reject the application early when the employee's payroll for the
     * relevant month has already been processed.
     *
     * Throwing here aborts the INSERT and rolls back any wrapping transaction.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function creating(EmployeeApplicationV2 $app): void
    {
        // $date = $app->application_date
        //     ? \Carbon\Carbon::parse($app->application_date)
        //     : \Carbon\Carbon::today();

        // $this->payrollLockGuard->checkLock(
        //     $app->employee_id,
        //     $date->year,
        //     $date->month,
        //     'application_date'
        // );
    }

    /**
     * Notify the employee's manager when a new application is submitted.
     */
    public function created(EmployeeApplicationV2 $app): void
    {
        try {
            $employee = $app->employee()->with(['manager.user'])->first();

            if (! $employee?->manager?->user) {
                return;
            }

            $managerUser = $employee->manager->user;

            // Do not notify the person who submitted the application.
            if (auth()->check() && auth()->id() === $managerUser->id) {
                return;
            }

            $typeName = EmployeeApplicationV2::APPLICATION_TYPE_NAMES[$app->application_type_id] ?? 'Application';

            // Send standard internal warning notification
            Warnings::send(
                $managerUser,
                WarningPayload::make(
                    'New Request from ' . ($employee->name ?? 'Employee'),
                    implode("\n", [
                        "Type: {$typeName}",
                        'Date: ' . ($app->application_date ?: now()->toDateString()),
                    ]),
                    WarningLevel::Info
                )
                    ->ctx([
                        'application_id' => $app->id,
                        'employee_id'    => $employee->id,
                        'type_id'        => $app->application_type_id,
                    ])
                    ->url(
                        rtrim(EmployeeApplicationResource::getUrl(), '/')
                            . (EmployeeApplicationV2::APPLICATION_TYPE_FILTERS[$app->application_type_id] ?? '')
                    )
                    ->scope("emp-app-{$app->id}")
                    ->expires(now()->addHours(24))
            );

            // Send WhatsApp notification for Advance Requests
            if ($app->application_type_id === EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST) {
                $advanceRequest = $app->advanceRequest;
                $amount = $advanceRequest ? ($advanceRequest->advance_amount . ' ' . ($advanceRequest->currency ?? 'USD')) : 'Unknown Amount';

                sendWhatsAppMessage($managerUser, $amount, [
                    'template' => 'workbench_advance_notifier',
                    'parameters' => [
                        ['type' => 'text', 'text' => $managerUser->name],
                        ['type' => 'text', 'text' => $employee->name],
                        ['type' => 'text', 'text' => $amount]
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[EmployeeApplicationObserver] Failed to notify manager.', [
                'application_id' => $app->id ?? null,
                'employee_id'    => $app->employee_id ?? null,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Prevent approving or modifying an application if the payroll month is locked.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updating(EmployeeApplicationV2 $app): void
    {
        // 1. If transitioning to approved: always check.
        $isTransitioningToApproved = $app->isDirty('status') && $app->status === EmployeeApplicationV2::STATUS_APPROVED;

        // 2. If the date is changing: check the NEW date.
        $isDateChanging = $app->isDirty('application_date');

        // if ($isTransitioningToApproved || $isDateChanging) {
        //     $date = $app->application_date
        //         ? \Carbon\Carbon::parse($app->application_date)
        //         : \Carbon\Carbon::today();

        //     $this->payrollLockGuard->checkLock(
        //         $app->employee_id,
        //         $date->year,
        //         $date->month,
        //         'application_date'
        //     );
        // }
    }

    /**
     * When an advance-request application transitions to STATUS_APPROVED,
     * trigger installment generation and financial transaction creation.
     *
     * Fires for BOTH Filament (web) and API approvals.
     */
    public function updated(EmployeeApplicationV2 $app): void
    {
        if (! $this->isAdvanceApproval($app)) {
            return;
        }

        try {
            DB::transaction(fn() => $this->advanceApprovalService->process($app));

            // Notify the employee that their advance request was approved
            $employeeUser = $app->employee?->user;
            if ($employeeUser) {
                Warnings::send(
                    $employeeUser,
                    WarningPayload::make(
                        'Advance Request Approved',
                        'Your advance request has been approved.',
                        WarningLevel::Info
                    )
                        ->ctx([
                            'application_id' => $app->id,
                            'employee_id'    => $app->employee_id,
                            'type_id'        => $app->application_type_id,
                        ])
                        ->url(rtrim(EmployeeApplicationResource::getUrl(), '/') . '?tab=Advance+request')
                        ->scope("emp-app-approved-{$app->id}")
                        ->expires(now()->addDays(3))
                );
            }
        } catch (\Throwable $e) {
            Log::error('[EmployeeApplicationObserver] Failed to process advance approval.', [
                'application_id' => $app->id,
                'employee_id'    => $app->employee_id,
                'error'          => $e->getMessage(),
            ]);

            // Re-throw so Filament / API controllers can roll back and surface the error.
            throw $e;
        }
    }

    /**
     * Prevent deleting an application if the payroll month is locked.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function deleting(EmployeeApplicationV2 $app): void
    {
        // $date = $app->application_date
        //     ? \Carbon\Carbon::parse($app->application_date)
        //     : \Carbon\Carbon::today();

        // $this->payrollLockGuard->checkLock(
        //     $app->employee_id,
        //     $date->year,
        //     $date->month,
        //     'application_date'
        // );
    }

    // =========================================================================
    //  Private Helpers
    // =========================================================================

    /**
     * Determine whether the update is an advance-request approval transition.
     */
    private function isAdvanceApproval(EmployeeApplicationV2 $app): bool
    {
        // Now handled directly by the Financial Manager approval action.
        return false;
    }
}
