<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Contracts;

use App\Modules\HR\EmployeeApplications\DTOs\ApplicationBranchContext;

interface ApplicationBranchRuleInterface
{
    public function supports(ApplicationBranchContext $context): bool;

    public function resolveBranchId(ApplicationBranchContext $context): ?int;
}
