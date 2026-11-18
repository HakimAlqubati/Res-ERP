<?php

declare(strict_types=1);

namespace App\Modules\HR\Employee\Listeners;

use App\Modules\HR\Employee\Contracts\UserSessionRevokerInterface;
use App\Modules\HR\Employee\Events\EmployeeAccessRevoked;

final class RevokeSessionsOnTerminationListener
{
    public function __construct(
        private readonly UserSessionRevokerInterface $sessionRevoker
    ) {}

    public function handle(EmployeeAccessRevoked $event): void
    {
        $this->sessionRevoker->revokeForUser($event->userId, $event->reason);
    }
}
