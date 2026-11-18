<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Models\User;
use App\Modules\HR\Employee\Events\EmployeeAccessRevoked;
use App\Rules\HR\Employee\CanRequestTerminationRule;
use App\Rules\HR\Employee\NoFutureTerminationApprovalRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeServiceTerminationObserver
{
    /**
     * Handle the EmployeeServiceTermination "creating" event.
     */

    
    public function creating(EmployeeServiceTermination $employeeServiceTermination): void
    {
        if (auth()->check()) {
            $employeeServiceTermination->created_by = auth()->id();
        }

        $employeeServiceTermination->loadMissing('employee');
        $employee = $employeeServiceTermination->employee
            ?? ($employeeServiceTermination->employee_id ? Employee::find($employeeServiceTermination->employee_id) : null);

        if (!$employeeServiceTermination->branch_id && $employee) {
            $employeeServiceTermination->branch_id = $employee->branch_id;
        }

        if ($employee && empty($employeeServiceTermination->employment_cycle)) {
            $employeeServiceTermination->employment_cycle = (int) ($employee->employment_cycle ?? 1);
        }

        // Prevent creating multiple active terminations within the current employment cycle.
        if ($employee) {
            CanRequestTerminationRule::check($employee);
        }
    }

    /**
     * Handle the EmployeeServiceTermination "created" event.
     */
    public function created(EmployeeServiceTermination $employeeServiceTermination): void
    {
        $employee = $employeeServiceTermination->employee;

        if ($employee?->user_id) {
            $userId = (int) $employee->user_id;
            $employeeId = (int) $employee->id;

            DB::afterCommit(static function () use ($userId, $employeeId, $employeeServiceTermination): void {
                EmployeeAccessRevoked::dispatch(
                    userId: $userId,
                    employeeId: $employeeId,
                    reason: "Termination request #{$employeeServiceTermination->id} created"
                );
            });
        }

        $hrUsers = User::whereHas('roles', function ($query) {
            $query->where('roles.id', 19);
        })->get();

        foreach ($hrUsers as $hr) {
            if ($hr->email) {
                \Illuminate\Support\Facades\Mail::raw(
                    "A new termination request has been created for the employee: " . ($employeeServiceTermination->employee->name ?? 'Unknown') . "\nTermination Date: " . ($employeeServiceTermination->termination_date ? $employeeServiceTermination->termination_date->format('Y-m-d') : 'Unknown'),
                    function ($message) use ($hr) {
                        $message->to($hr->email)
                                ->subject('Notification: New Employee Termination Request');
                    }
                );
            }
        }
    }

    /**
     * Handle the EmployeeServiceTermination "updating" event.
     */
    public function updating(EmployeeServiceTermination $employeeServiceTermination): void
    {
        if (auth()->check()) {
            $employeeServiceTermination->updated_by = auth()->id();
        }

        if (
            $employeeServiceTermination->isDirty('status') &&
            $employeeServiceTermination->status === EmployeeServiceTermination::STATUS_APPROVED
        ) {
            if ($employeeServiceTermination->termination_date && $employeeServiceTermination->termination_date->isFuture()) {
                // Intercept future termination approval: schedule for auto-approval instead of immediate approval
                $employeeServiceTermination->status = EmployeeServiceTermination::STATUS_PENDING;
                $employeeServiceTermination->auto_approve = true;
                $employeeServiceTermination->scheduled_approver_id = auth()->id();
                $employeeServiceTermination->approved_at = null;
                $employeeServiceTermination->approved_by = null;
            } else {
                Validator::make(
                    ['termination_date' => $employeeServiceTermination->termination_date],
                    ['termination_date' => new NoFutureTerminationApprovalRule($employeeServiceTermination->termination_date)]
                )->validate();
            }
        }
    }

    /**
     * Handle the EmployeeServiceTermination "updated" event.
     * Note: Deactivation logic has been moved to EmployeeLifecycleService
     * to ensure explicit control and consistency.
     */
    public function updated(EmployeeServiceTermination $employeeServiceTermination): void
    {
        // Complex side-effects like deactivating employees are now
        // handled explicitly in the EmployeeLifecycleService.

        if ($employeeServiceTermination->isDirty('status') && $employeeServiceTermination->status === EmployeeServiceTermination::STATUS_APPROVED) {
            $financeManagers = User::whereHas('roles', function ($query) {
                $query->where('roles.id', 16);
            })->get();

            foreach ($financeManagers as $manager) {
                if ($manager->email) {
                    \Illuminate\Support\Facades\Mail::raw(
                        "The termination request has been approved for the employee: " . ($employeeServiceTermination->employee->name ?? 'Unknown') . "\nTermination Date: " . ($employeeServiceTermination->termination_date ? $employeeServiceTermination->termination_date->format('Y-m-d') : 'Unknown'),
                        function ($message) use ($manager) {
                            $message->to($manager->email)
                                    ->subject('Notification: Employee Termination Approved');
                        }
                    );
                }
            }
        }
    }
}
