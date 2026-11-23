<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function view(User $user, TenantInvoice $invoice): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function create(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function update(User $user, TenantInvoice $invoice): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function delete(User $user, TenantInvoice $invoice): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canAccessCentralPanel();
    }

    public function markAsPaid(User $user, TenantInvoice $invoice): bool
    {
        return $user->canAccessCentralPanel() && $invoice->isUnpaid();
    }
}
