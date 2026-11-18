<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Rules\Notifications;

use App\Models\EmployeeApplicationV2;
use App\Models\User;
use App\Modules\HR\EmployeeApplications\Contracts\ApplicationApproverRuleInterface;

/**
 * Fallback notification recipient rule preserving existing behavior for
 * standard applications (notifying the employee's direct manager).
 */
final class DefaultEmployeeManagerRule implements ApplicationApproverRuleInterface
{
    public function supports(EmployeeApplicationV2 $application): bool
    {
        return true;
    }

    public function resolveRecipient(EmployeeApplicationV2 $application): ?User
    {
        $application->loadMissing('employee.manager.user');

        return $application->employee?->manager?->user;
    }
}
