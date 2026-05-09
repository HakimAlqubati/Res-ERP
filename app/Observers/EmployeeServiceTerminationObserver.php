<?php

namespace App\Observers;

use App\Models\EmployeeServiceTermination;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

        if (!$employeeServiceTermination->branch_id) {
            // Retrieve employee if not already loaded
            $employeeServiceTermination->loadMissing('employee');
            $employeeServiceTermination->branch_id = $employeeServiceTermination->employee?->branch_id;
        }

        // Prevent creating multiple terminations if one is already pending or approved.
        $hasActiveTermination = EmployeeServiceTermination::where('employee_id', $employeeServiceTermination->employee_id)
            ->whereIn('status', [
                EmployeeServiceTermination::STATUS_PENDING, 
                EmployeeServiceTermination::STATUS_APPROVED
            ])
            ->exists();

        if ($hasActiveTermination) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'Employee already has an active termination request.'
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
