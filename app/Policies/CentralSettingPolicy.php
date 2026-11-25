<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CentralSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CentralSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function view(User $user, CentralSetting $setting): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function create(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function update(User $user, CentralSetting $setting): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function delete(User $user, CentralSetting $setting): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }
}
