<?php

namespace App\Observers;

use App\Models\Employee;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee)
    {
        // Access the related user model
        $user = $employee->user;

        if($user){

            // Check if 'email' or 'phone_number' changed
            if ($employee->isDirty('email')) {
                $user->email = $employee->email;
            }
            if ($employee->isDirty('phone_number')) {
                $user->phone_number = $employee->phone_number;
            }
    
            if ($user->isDirty('name')) {
                $employee->name = $user->name;
            }
     
            // if ($user->isDirty('branch_id')) {
            //     $employee->branch_id = $user->branch_id;
            // }
     
            // Save changes to the user model
            $user->save();
        }
    }


    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "restored" event.
     */
    public function restored(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "force deleted" event.
     */
    public function forceDeleted(Employee $employee): void
    {
        //
    }
}
