<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Rules\BranchResolution;

use App\Modules\HR\EmployeeApplications\Contracts\ApplicationBranchRuleInterface;
use App\Modules\HR\EmployeeApplications\DTOs\ApplicationBranchContext;

/**
 * Preserves an explicitly provided branch_id (e.g. meal requests or explicit overrides)
 * when no higher-priority historical rule overrides it.
 */
final class ExplicitBranchRule implements ApplicationBranchRuleInterface
{
    public function supports(ApplicationBranchContext $context): bool
    {
        return $context->explicitBranchId !== null && $context->explicitBranchId > 0;
    }

    public function resolveBranchId(ApplicationBranchContext $context): ?int
    {
        return $context->explicitBranchId;
    }
}
