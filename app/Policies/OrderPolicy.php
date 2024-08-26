<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function view(User $user)
    {
        return $user->can('view_order');
    }

    public function viewAny(User $user)
    {
        return $user->can('view_any_order');
    }

    public function view_any(User $user)
    {
        return $user->can('view_any');
    }
}
