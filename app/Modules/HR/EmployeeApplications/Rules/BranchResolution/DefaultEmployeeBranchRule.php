<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Rules\BranchResolution;

use App\Modules\HR\EmployeeApplications\Contracts\ApplicationBranchRuleInterface;
use App\Modules\HR\EmployeeApplications\DTOs\ApplicationBranchContext;

/**
 * Fallback rule returning the employee's current branch_id, preserving all
 * legacy behavior when no specialized rule matches or no historical log exists.
 */
final class DefaultEmployeeBranchRule implements ApplicationBranchRuleInterface
{
    public function supports(ApplicationBranchContext $context): bool
    {
        return true;
    }

    public function resolveBranchId(ApplicationBranchContext $context): ?int
    {
        return $context->employee->branch_id ? (int) $context->employee->branch_id : null;
    }
}
