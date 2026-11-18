<?php

declare(strict_types=1);

namespace App\Modules\HR\Employee\Contracts;

interface UserSessionRevokerInterface
{
    /**
     * Revoke all active API tokens and web sessions for the given user.
     */
    public function revokeForUser(int $userId, string $reason = ''): void;
}
