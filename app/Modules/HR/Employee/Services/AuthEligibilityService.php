<?php

declare(strict_types=1);

namespace App\Modules\HR\Employee\Services;

use App\Models\User;

/**
 * Single source of truth for checking whether a user/employee
 * is allowed to maintain an active session or log in.
 */
final class AuthEligibilityService
{
    /**
     * Return a human-readable block message if the user cannot authenticate,
     * or null when the user is eligible.
     */
    public function getBlockReason(User $user): ?string
    {
        if (! (bool) $user->active) {
            return 'Your account is inactive. Please contact the administrator.';
        }

        $employee = $user->employee;
        if ($employee) {
            if (! (bool) $employee->active) {
                return 'Your employee profile is inactive. Please contact the administrator.';
            }

            if ($employee->pendingTerminationRequest()->exists()) {
                return 'Your account is pending termination. Please contact the administrator.';
            }
        }

        return null;
    }

    public function canAuthenticate(User $user): bool
    {
        return $this->getBlockReason($user) === null;
    }
}
