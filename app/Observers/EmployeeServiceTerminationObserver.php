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
    }
}
