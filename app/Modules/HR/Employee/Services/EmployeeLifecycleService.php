<?php

namespace App\Modules\HR\Employee\Services;

use App\Models\AppLog;
use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Models\User;
use App\Modules\HR\Employee\Events\EmployeeAccessRevoked;
use App\Rules\HR\Employee\CanRequestTerminationRule;
use App\Rules\HR\Employee\EmployeeFinancialClearanceRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Service class for managing Employee Lifecycle events professionally.
 * Handles termination requests, approvals, rejections, and rehiring logic.
 */
class EmployeeLifecycleService
{
    /**
     * Request service termination for an employee.
     *
     * @param Employee $employee
     * @param array $data
     * @return EmployeeServiceTermination
     * @throws Exception
     */
    public function requestTermination(Employee $employee, array $data): EmployeeServiceTermination
    {
        return DB::transaction(function () use ($employee, $data) {
            $lockedEmployee = Employee::lockForUpdate()->findOrFail($employee->id);

            CanRequestTerminationRule::check($lockedEmployee);
            EmployeeFinancialClearanceRule::check($lockedEmployee);

            $termination = $lockedEmployee->serviceTerminations()->create([
                'employment_cycle'   => (int) ($lockedEmployee->employment_cycle ?? 1),
                'termination_date'   => $data['termination_date'],
                'termination_reason' => $data['termination_reason'],
                'notes'              => $data['notes'] ?? null,
                'status'             => EmployeeServiceTermination::STATUS_PENDING,
            ]);

            AppLog::write(
                message: "Termination request created for Employee {$lockedEmployee->name} (#{$lockedEmployee->id})",
                level: AppLog::LEVEL_INFO,
                context: 'HR_TERMINATION_REQUEST',
                extra: ['employee_id' => $lockedEmployee->id, 'termination_id' => $termination->id]
            );

            return $termination;
        });
    }

    /**
     * Approve a pending termination request.
     *
     * @param EmployeeServiceTermination $termination
     * @param int|null $approverId  Explicit approver ID (used by the cron job). Falls back to auth()->id().
     * @throws Exception
     */
    public function approveTermination(EmployeeServiceTermination $termination, ?int $approverId = null): void
    {
        DB::transaction(function () use ($termination, $approverId) {
            $lockedTermination = EmployeeServiceTermination::lockForUpdate()->findOrFail($termination->id);
            $employee = $lockedTermination->employee
                ? Employee::lockForUpdate()->find($lockedTermination->employee_id)
                : null;

            if ($employee) {
                EmployeeFinancialClearanceRule::check($employee);
            }

            $lockedTermination->update([
                'status'      => EmployeeServiceTermination::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $approverId ?? auth()->id(),
            ]);

            $lockedTermination->refresh();

            // Observer::updating may defer future terminations back to STATUS_PENDING (auto_approve=true).
            // Only deactivate the employee and user when the termination is actually approved.
            if ($lockedTermination->status !== EmployeeServiceTermination::STATUS_APPROVED) {
                return;
            }

            if ($employee) {
                $employee->update(['active' => 0]);

                if ($employee->user_id) {
                    $user = User::withTrashed()->find($employee->user_id);
                    if ($user) {
                        $user->update(['active' => 0]);
                    }

                    $userId = (int) $employee->user_id;
                    $employeeId = (int) $employee->id;
                    DB::afterCommit(static function () use ($userId, $employeeId, $lockedTermination): void {
                        EmployeeAccessRevoked::dispatch(
                            userId: $userId,
                            employeeId: $employeeId,
                            reason: "Termination #{$lockedTermination->id} approved"
                        );
                    });
                }
            }

            AppLog::write(
                message: "Termination approved for Employee " . ($employee?->name ?? "#{$lockedTermination->employee_id}") . " (#{$lockedTermination->employee_id})",
                level: AppLog::LEVEL_INFO,
                context: 'HR_TERMINATION_APPROVED',
                extra: ['employee_id' => $employee?->id, 'termination_id' => $lockedTermination->id]
            );
        });
    }

    /**
     * Reject a pending termination request.
     *
     * @param EmployeeServiceTermination $termination
     * @param array $data
     * @throws Exception
     */
    public function rejectTermination(EmployeeServiceTermination $termination, array $data): void
    {
        DB::transaction(function () use ($termination, $data) {
            $termination->update([
                'status'           => EmployeeServiceTermination::STATUS_REJECTED,
                'rejected_at'      => now(),
                'rejected_by'      => auth()->id(),
                'rejection_reason' => $data['rejection_reason'] ?? null
            ]);

            AppLog::write(
                message: "Termination request rejected for Employee {$termination->employee->name} (#{$termination->employee->id})",
                level: AppLog::LEVEL_INFO,
                context: 'HR_TERMINATION_REJECTED',
                extra: ['employee_id' => $termination->employee_id, 'termination_id' => $termination->id]
            );
        });
    }

    /**
     * Rehire a terminated employee.
     *
     * @param Employee $employee
     * @param array $data
     * @throws Exception
     */
    public function rehire(Employee $employee, array $data): void
    {
        DB::transaction(function () use ($employee, $data) {
            $lockedEmployee = Employee::lockForUpdate()->findOrFail($employee->id);
            $nextCycle = ((int) ($lockedEmployee->employment_cycle ?? 1)) + 1;

            // 1. Reactivate employee, update join date, and advance employment cycle
            //    so prior approved terminations remain intact as historical audit records.
            $lockedEmployee->update([
                'active'           => 1,
                'employment_cycle' => $nextCycle,
                'join_date'        => $data['join_date'],
            ]);

            // 2. Reactivate/Restore linked user if exists
            if ($lockedEmployee->user_id) {
                $user = User::withTrashed()->find($lockedEmployee->user_id);
                if ($user) {
                    if (method_exists($user, 'trashed') && $user->trashed()) {
                        $user->restore();
                    }
                    $user->update(['active' => 1]);
                }
            }

            // 3. Cancel any lingering pending termination requests without altering approved history
            $lockedEmployee->serviceTerminations()
                ->where('status', EmployeeServiceTermination::STATUS_PENDING)
                ->update([
                    'status' => EmployeeServiceTermination::STATUS_CANCEL,
                    'notes'  => (isset($data['notes']) && $data['notes'] ? $data['notes'] . " (Rehired)" : "Rehired")
                ]);

            // 4. Log the rehire event
            AppLog::write(
                message: "Employee {$lockedEmployee->name} (#{$lockedEmployee->id}) rehired successfully (cycle #{$nextCycle}) with join date: {$data['join_date']}",
                level: AppLog::LEVEL_INFO,
                context: 'HR_REHIRE',
                extra: [
                    'employee_id'      => $lockedEmployee->id,
                    'employment_cycle' => $nextCycle,
                    'join_date'        => $data['join_date'],
                    'notes'            => $data['notes'] ?? null
                ]
            );
        });
    }
}
