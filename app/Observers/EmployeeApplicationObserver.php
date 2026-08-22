<?php

namespace App\Observers;

use App\Enums\Warnings\WarningLevel;
use App\Facades\Warnings;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\EmployeeApplicationV2;
use App\Models\User;
use App\Services\HR\Applications\AdvanceRequest\AdvanceApprovalService;
use App\Services\HR\Applications\LeaveRequest\LeaveApprovalService;
use App\Services\HR\Payroll\PayrollLockGuard;
use App\Services\Warnings\WarningPayload;
use App\Services\WhatsApp\Contracts\WhatsAppServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        private readonly LeaveApprovalService $leaveApprovalService,
        private readonly PayrollLockGuard $payrollLockGuard,
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
     * @throws ValidationException
     */
    public function creating(EmployeeApplicationV2 $app): void
    {
        if (! auth()->user()->can_create_advance) {
            if (
                $app->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST
                || $app->application_type_id == EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST
            ) {
                throw ValidationException::withMessages([
                    'errors' => ['Cannot Create Advance Request'],
                ]);
            }
        }
        $date = $app->application_date
            ? Carbon::parse($app->application_date)
            : Carbon::today();

        $this->payrollLockGuard->checkLock(
            $app->employee_id,
            $date->year,
            $date->month,
            'application_date'
        );
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
                    'New Request from '.($employee->name ?? 'Employee'),
                    implode("\n", [
                        "Type: {$typeName}",
                        'Date: '.($app->application_date ?: now()->toDateString()),
                    ]),
                    WarningLevel::Info
                )
                    ->ctx([
                        'application_id' => $app->id,
                        'employee_id' => $employee->id,
                        'type_id' => $app->application_type_id,
                    ])
                    ->url(
                        rtrim(EmployeeApplicationResource::getUrl(), '/')
                            .(EmployeeApplicationV2::APPLICATION_TYPE_FILTERS[$app->application_type_id] ?? '')
                    )
                    ->scope("emp-app-{$app->id}")
                    ->expires(now()->addHours(24))
            );

            // Send WhatsApp notification for Advance Requests
            if ($app->application_type_id === EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST) {
                $advanceRequest = $app->advanceRequest;
                $amount = $advanceRequest ? ($advanceRequest->advance_amount) : 'Unknown Amount';

                sendWhatsAppMessage($managerUser, $amount, [
                    'template' => 'workbench_advance_notifier',
                    'parameters' => [
                        ['type' => 'text', 'text' => $managerUser->name],
                        ['type' => 'text', 'text' => $employee->name],
                        ['type' => 'text', 'text' => $amount],
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[EmployeeApplicationObserver] Failed to notify manager.', [
                'application_id' => $app->id ?? null,
                'employee_id' => $app->employee_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Prevent approving or modifying an application if the payroll month is locked.
     *
     * @throws ValidationException
     */
    public function updating(EmployeeApplicationV2 $app): void
    {
        if ($app->getOriginal('status') == EmployeeApplicationV2::STATUS_APPROVED &&
          $app->toArray()['status'] == EmployeeApplicationV2::STATUS_REJECTED) {
            return;
        }

        $this->checkApplicationModelLock($app);

        // 1. Determine the date to check against (Original date in DB)
        // To prevent modifying any data in an already locked month.
        $originalDate = $app->getOriginal('application_date')
            ? Carbon::parse($app->getOriginal('application_date'))
            : ($app->application_date ? Carbon::parse($app->application_date) : Carbon::today());

        $this->payrollLockGuard->checkLock(
            $app->employee_id,
            $originalDate->year,
            $originalDate->month,
            'application_date'
        );

        // 2. If the application_date itself is changing, ensure the NEW date is also not in a locked month.
        if ($app->isDirty('application_date') && ! empty($app->application_date)) {
            $newDate = Carbon::parse($app->application_date);

            if ($newDate->month !== $originalDate->month || $newDate->year !== $originalDate->year) {
                $this->payrollLockGuard->checkLock(
                    $app->employee_id,
                    $newDate->year,
                    $newDate->month,
                    'application_date'
                );
            }
        }
    }

    /**
     * When an advance-request application transitions to STATUS_APPROVED,
     * trigger installment generation and financial transaction creation.
     *
     * Fires for BOTH Filament (web) and API approvals.
     */
    public function updated(EmployeeApplicationV2 $app): void
    {
        // ── Advance Request: WhatsApp notification to Financial Managers ──────
        if (
            $app->isDirty('status')
            && $app->status === EmployeeApplicationV2::STATUS_APPROVED
            && $app->application_type_id === EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST
        ) {
            $this->notifyFinancialManagersOfAdvanceApproved($app);
        }

        // ── Advance Request: installment generation ───────────────────────────
        if ($this->isAdvanceApproval($app)) {
            try {
                DB::transaction(fn () => $this->advanceApprovalService->process($app));

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
                                'employee_id' => $app->employee_id,
                                'type_id' => $app->application_type_id,
                            ])
                            ->url(rtrim(EmployeeApplicationResource::getUrl(), '/').'?tab=Advance+request')
                            ->scope("emp-app-approved-{$app->id}")
                            ->expires(now()->addDays(3))
                    );
                }
            } catch (\Throwable $e) {
                Log::error('[EmployeeApplicationObserver] Failed to process advance approval.', [
                    'application_id' => $app->id,
                    'employee_id' => $app->employee_id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        // ── Leave Request: balance update on status transition ────────────────
        $this->handleLeaveStatusTransition($app);
    }

    /**
     * Dispatch the correct LeaveApprovalService method based on the
     * previous → current status transition.
     *
     * Only fires when:
     *  a) The application type is a leave request.
     *  b) The `status` field actually changed.
     */
    private function handleLeaveStatusTransition(EmployeeApplicationV2 $app): void
    {
        if ($app->application_type_id !== EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
            return;
        }

        if (! $app->isDirty('status')) {
            return;
        }

        $previousStatus = $app->getOriginal('status');
        $currentStatus = $app->status;

        try {
            DB::transaction(function () use ($app, $previousStatus, $currentStatus) {
                match (true) {

                    // ✅ Any status → Approved: add to used_days
                    $currentStatus === EmployeeApplicationV2::STATUS_APPROVED => $this->leaveApprovalService->onApproved($app),

                    // 🔄 Was Approved → now Pending: undo approve (move from used to pending)
                    $currentStatus === EmployeeApplicationV2::STATUS_PENDING
                    && $previousStatus === EmployeeApplicationV2::STATUS_APPROVED => $this->leaveApprovalService->onRevertedToPendingFromApproved($app),

                    // ⏳ Any OTHER status → Pending: add to pending_days
                    $currentStatus === EmployeeApplicationV2::STATUS_PENDING => $this->leaveApprovalService->onPending($app),

                    // ❌ Was Approved → now Rejected: revert used_days
                    $currentStatus === EmployeeApplicationV2::STATUS_REJECTED
                    && $previousStatus === EmployeeApplicationV2::STATUS_APPROVED => $this->leaveApprovalService->onRejectedFromApproved($app),

                    // ❌ Was Pending → now Rejected: revert pending_days
                    $currentStatus === EmployeeApplicationV2::STATUS_REJECTED
                    && $previousStatus === EmployeeApplicationV2::STATUS_PENDING => $this->leaveApprovalService->onRejectedFromPending($app),

                    default => null,
                };

                // Clear cache for the leave dates
                if ($app->application_type_id === EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
                    $leave = $app->leaveRequest;
                    if ($leave && $leave->start_date && $leave->end_date) {
                        $start = Carbon::parse($leave->start_date);
                        $end = Carbon::parse($leave->end_date);
                        while ($start->lte($end)) {
                            clearEmployeeDailyAttendanceCache($app->employee_id, $start->toDateString());
                            $start->addDay();
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('[EmployeeApplicationObserver] Failed to update leave balance.', [
                'application_id' => $app->id,
                'employee_id' => $app->employee_id,
                'previous_status' => $previousStatus,
                'current_status' => $currentStatus,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Prevent deleting an application if the payroll month is locked.
     *
     * @throws ValidationException
     */
    public function deleting(EmployeeApplicationV2 $app): void
    {
        $date = $app->application_date
            ? Carbon::parse($app->application_date)
            : Carbon::today();

        $this->payrollLockGuard->checkLock(
            $app->employee_id,
            $date->year,
            $date->month,
            'application_date'
        );
    }

    /**
     * Restore balance when an application is deleted.
     */
    public function deleted(EmployeeApplicationV2 $app): void
    {
        if ($app->application_type_id === EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST) {
            try {
                DB::transaction(function () use ($app) {
                    if ($app->status === EmployeeApplicationV2::STATUS_APPROVED) {
                        $this->leaveApprovalService->onRejectedFromApproved($app);
                    } elseif ($app->status === EmployeeApplicationV2::STATUS_PENDING) {
                        $this->leaveApprovalService->onRejectedFromPending($app);
                    }
                });
            } catch (\Throwable $e) {
                Log::error('[EmployeeApplicationObserver] Failed to restore balance on delete.', [
                    'application_id' => $app->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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

    public function checkApplicationModelLock(EmployeeApplicationV2 $app): void
    {
        $targetDate = null;
        switch ($app->application_type_id) {
            case EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST:
                $targetDate = $app->missedCheckinRequest?->date;
                break;
            case EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST:
                $targetDate = $app->missedCheckoutRequest?->date;
                break;
            case EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST:
                $targetDate = $app->leaveRequest?->start_date;
                break;
            case EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST:
                $targetDate = $app->advanceRequest?->date ?? $app->application_date;
                break;
            case EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST:
                $targetDate = $app->mealRequest?->date ?? $app->application_date;
                break;
            default:
                $targetDate = $app->application_date;
                break;
        }
        if ($targetDate) {
            $parsedDate = Carbon::parse($targetDate);
            $this->payrollLockGuard->checkLock($app->employee_id, $parsedDate->year, $parsedDate->month, 'application_date');
        }
    }

    /**
     * Notify all Financial Managers that an Advance Request has been approved by the manager.
     */
    private function notifyFinancialManagersOfAdvanceApproved(EmployeeApplicationV2 $app): void
    {
        try {
            $employeeName = $app->employee?->name ?? 'Unknown Employee';
            $managerName = auth()->user()?->name ?? 'Manager';

            $advanceRequest = $app->advanceRequest;
            $amount = $advanceRequest ? ($advanceRequest->advance_amount) : 'Unknown Amount';

            $whatsappService = app(WhatsAppServiceInterface::class);

            // Fetch Financial Managers (role ID 16 based on User::isFinanceManager)
            $financeManagers = User::whereHas('roles', function ($query) {
                $query->where('roles.id', 16);
            })->whereNotNull('phone_number')->get();
            Log::info('financial managers', $financeManagers->toArray());
            foreach ($financeManagers as $manager) {
                if (! empty($manager->phone_number)) {
                    $whatsappService->sendTemplate(
                        to: $manager->phone_number,
                        templateName: 'workbench_advance_notifier',
                        parameters: [
                            $managerName,
                            $employeeName,
                            $amount,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[EmployeeApplicationObserver] Failed to send WhatsApp to Financial Managers.', [
                'application_id' => $app->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
