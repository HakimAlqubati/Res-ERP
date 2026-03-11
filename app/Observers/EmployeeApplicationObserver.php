<?php

namespace App\Observers;

use App\Enums\Warnings\WarningLevel;
use App\Facades\Warnings;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use App\Models\EmployeeApplicationV2;
use App\Services\HR\Applications\AdvanceRequest\AdvanceApprovalService;
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
    ) {}

    // =========================================================================
    //  Event Hooks
    // =========================================================================

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
        } catch (\Throwable $e) {
            Log::warning('[EmployeeApplicationObserver] Failed to notify manager.', [
                'application_id' => $app->id ?? null,
                'employee_id'    => $app->employee_id ?? null,
                'error'          => $e->getMessage(),
            ]);
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

    // =========================================================================
    //  Private Helpers
    // =========================================================================

    /**
     * Determine whether the update is an advance-request approval transition.
     */
    private function isAdvanceApproval(EmployeeApplicationV2 $app): bool
    {
        return $app->wasChanged('status')
            && $app->status === EmployeeApplicationV2::STATUS_APPROVED
            && $app->application_type_id === EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST;
    }
}
