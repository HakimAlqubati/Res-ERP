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
     */
    public function updated(EmployeeServiceTermination $employeeServiceTermination): void
    {
        // Check if status changed to approved
        if (
            $employeeServiceTermination->isDirty('status') &&
            $employeeServiceTermination->status === EmployeeServiceTermination::STATUS_APPROVED
        ) {

            DB::transaction(function () use ($employeeServiceTermination) {
                // Deactivate Employee
                $employee = $employeeServiceTermination->employee;
                if ($employee) {
                    $employee->active = 0; // or false
                    $employee->saveQuietly(); // Use saveQuietly to avoid triggering other observers if not needed, or save() if needed.

                    // Deactivate Linked User
                    if ($employee->user_id) {
                        $user = User::withTrashed()->find($employee->user_id);
                        if ($user) {
                            $user->active = 0;
                            $user->saveQuietly();
                        }
                    }
                }
            });
        }
    }
}
