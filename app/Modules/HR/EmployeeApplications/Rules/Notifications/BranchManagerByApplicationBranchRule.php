<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Rules\Notifications;

use App\Models\Branch;
use App\Models\EmployeeApplicationV2;
use App\Models\User;
use App\Modules\HR\EmployeeApplications\Contracts\ApplicationApproverRuleInterface;

/**
 * Routes missed check-in / check-out notifications to the manager of the
 * application's target branch (resolved from historical branch logs), rather
 * than the employee's current branch manager after transfer.
 */
final class BranchManagerByApplicationBranchRule implements ApplicationApproverRuleInterface
{
    private const SUPPORTED_TYPES = [
        EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
        EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
    ];

    public function supports(EmployeeApplicationV2 $application): bool
    {
        return in_array((int) $application->application_type_id, self::SUPPORTED_TYPES, true)
            && ! empty($application->branch_id);
    }

    public function resolveRecipient(EmployeeApplicationV2 $application): ?User
    {
        $branch = Branch::withoutGlobalScopes()
            ->with('user')
            ->find($application->branch_id);

        return $branch?->user;
    }
}
