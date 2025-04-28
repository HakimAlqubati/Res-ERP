<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkPeriod;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkPeriodPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_work::period');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkPeriod $workPeriod): bool
    {
        return $user->can('view_work::period');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_work::period');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkPeriod $workPeriod): bool
    {
        return $user->can('update_work::period');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkPeriod $workPeriod): bool
    {
        return $user->can('delete_work::period');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_work::period');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, WorkPeriod $workPeriod): bool
    {
        return $user->can('force_delete_work::period');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_work::period');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, WorkPeriod $workPeriod): bool
    {
        return $user->can('restore_work::period');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_work::period');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, WorkPeriod $workPeriod): bool
    {
        return $user->can('replicate_work::period');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_work::period');
    }
}
