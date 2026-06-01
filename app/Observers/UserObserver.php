<?php

namespace App\Observers;

use App\Models\User;
use App\Rules\UserBranchCannotBeChanged;
use Illuminate\Support\Facades\Validator;

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
     * Handle the User "updating" event.
     *
     * Prevents changing branch_id directly on a user who has an employee.
     * Branch transfers must go through the employee record.
     */
    public function updating(User $user): void
    {
        Validator::make(
            ['branch_id' => $user->branch_id],
            ['branch_id' => [new UserBranchCannotBeChanged($user)]]
        )->validate();
    }

    /**
     * Handle the User "updated" event.
     *
     * Syncs relevant user fields to the associated employee record.
     */
    public function updated(User $user): void
    {
        $employee = $user->employee;

        if (! $employee) {
            return;
        }

        $updates = [];

        if ($user->wasChanged('email')) {
            $updates['email'] = $user->email;
        }
        if ($user->wasChanged('phone_number')) {
            $updates['phone_number'] = $user->phone_number;
        }
        if ($user->wasChanged('name')) {
            $updates['name'] = $user->name;
        }
        if ($user->wasChanged('active')) {
            $updates['active'] = $user->active;
        }
        if ($user->wasChanged('gender')) {
            $updates['gender'] = $user->gender;
        }
        if ($user->wasChanged('nationality')) {
            $updates['nationality'] = $user->nationality;
        }

        // Always keep employee_type in sync with user_type
        $updates['employee_type'] = $user->user_type;

        if ($user->wasChanged('branch_id')) {
            $updates['branch_id'] = $user->branch_id;

            // Reset manager when branch changes, unless a new owner was set simultaneously
            if (! $user->wasChanged('owner_id')) {
                $updates['manager_id'] = null;
            }
        }

        if ($user->wasChanged('owner_id')) {
            $managerEmployee = \App\Models\User::find($user->owner_id)?->employee;
            $updates['manager_id'] = $managerEmployee?->id;
        }

        if (! empty($updates)) {
            $employee->updateQuietly($updates);
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
