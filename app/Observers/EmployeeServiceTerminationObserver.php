<?php

namespace App\Observers;

use App\Models\EmployeeServiceTermination;
use App\Models\User;
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

        if (!$employeeServiceTermination->branch_id || !$employeeServiceTermination->service_start_date) {
            $employeeServiceTermination->loadMissing('employee');
            if (!$employeeServiceTermination->branch_id) {
                $employeeServiceTermination->branch_id = $employeeServiceTermination->employee?->branch_id;
            }
            if (!$employeeServiceTermination->service_start_date && $employeeServiceTermination->employee?->join_date) {
                $employeeServiceTermination->service_start_date = $employeeServiceTermination->employee->join_date;
            }
        }

        // Prevent creating multiple pending termination requests for the same employee
        $hasPendingTermination = EmployeeServiceTermination::where('employee_id', $employeeServiceTermination->employee_id)
            ->where('status', EmployeeServiceTermination::STATUS_PENDING)
            ->exists();

        if ($hasPendingTermination) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'Employee already has a pending termination request.'
            ]);
        }

        // Prevent requesting termination for an already inactive employee
        if ($employeeServiceTermination->employee && ! $employeeServiceTermination->employee->active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'Cannot request termination for an employee who is already inactive.'
            ]);
        }
    }

    /**
     * Handle the EmployeeServiceTermination "created" event.
     */
    public function created(EmployeeServiceTermination $employeeServiceTermination): void
    {
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
