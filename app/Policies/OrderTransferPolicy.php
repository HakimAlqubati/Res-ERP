<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderTransferPolicy
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
        return $user->can('view_transfer::order');
    }

    public function viewAny(User $user)
    {
        return $user->can('view_any_transfer::order');
    }

    public function view_any(User $user)
    {
        return $user->can('view_any::order');
    }

}
