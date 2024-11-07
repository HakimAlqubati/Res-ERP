<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Access the related employee model
        $employee = $user->employee;

        if ($employee) {

            // Check if 'email' or 'phone_number' has changed in the user model
            if ($user->isDirty('email')) {
                $employee->email = $user->email;
            }
            if ($user->isDirty('phone_number')) {
                $employee->phone_number = $user->phone_number;
            }
            if ($user->isDirty('name')) {
                $employee->name = $user->name;
            }

            if ($user->isDirty('branch_id')) {
                $employee->branch_id = $user->branch_id;
            }

            // Save changes to the employee model
            $employee->save();
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
