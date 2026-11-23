<?php

namespace App\Policies;

use App\Models\CustomTenantModel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Multitenancy\Models\Tenant;

class TenantPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function view(User $user, Tenant|CustomTenantModel $tenant): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function create(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function update(User $user, Tenant|CustomTenantModel $tenant): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function delete(User $user, Tenant|CustomTenantModel $tenant): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function restore(User $user, Tenant|CustomTenantModel $tenant): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function restoreAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function forceDelete(User $user, Tenant|CustomTenantModel $tenant): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }
}
