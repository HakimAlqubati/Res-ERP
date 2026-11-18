<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Contracts;

use App\Models\EmployeeApplicationV2;
use App\Models\User;

interface ApplicationApproverRuleInterface
{
    public function supports(EmployeeApplicationV2 $application): bool;

    public function resolveRecipient(EmployeeApplicationV2 $application): ?User;
}
