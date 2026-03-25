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
