<?php

namespace App\Policies\FakeModelReports;

use App\Models\User;
use App\Models\FakeModelReports\StoreReportReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoreReportReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_reports::stores::report');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StoreReportReport $storeReportReport): bool
    {
        return $user->can('view_reports::stores::report');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_reports::stores::report');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StoreReportReport $storeReportReport): bool
    {
        return $user->can('update_reports::stores::report');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StoreReportReport $storeReportReport): bool
    {
        return $user->can('delete_reports::stores::report');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_reports::stores::report');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, StoreReportReport $storeReportReport): bool
    {
        return $user->can('force_delete_reports::stores::report');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_reports::stores::report');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, StoreReportReport $storeReportReport): bool
    {
        return $user->can('restore_reports::stores::report');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_reports::stores::report');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, StoreReportReport $storeReportReport): bool
    {
        return $user->can('replicate_reports::stores::report');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_reports::stores::report');
    }
}
