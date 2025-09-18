<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task') || $user->can('view_own_task');
    }
    public function viewOwn(User $user): bool
    {
        return $user->can('view_own_task');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->can('view_task');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
        return $user->can('create_task');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->can('update_task');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->can('delete_task');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return $user->can('restore_task');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->can('forse_delete_task');
    }

  
    
    public function rating(User $user): bool
    {
        return $user->can('rating_task');
    }

    public function addComment(User $user): bool
    {
        return $user->can('add_comment_task');
    }
   
    public function addPhoto(User $user): bool
    {
        return $user->can('add_photo_task');
    }
   
    public function moveStatus(User $user): bool
    {
        return $user->can('move_status_task');
    }
}
