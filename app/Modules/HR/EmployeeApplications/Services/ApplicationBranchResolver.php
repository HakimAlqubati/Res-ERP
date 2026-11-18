<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Services;

use App\Modules\HR\EmployeeApplications\Contracts\ApplicationBranchRuleInterface;
use App\Modules\HR\EmployeeApplications\DTOs\ApplicationBranchContext;

/**
 * Resolves the target branch ID for an employee application by evaluating
 * registered branch-resolution rules in priority order.
 */
final class ApplicationBranchResolver
{
    /** @var array<int, ApplicationBranchRuleInterface> */
    private array $rules;

    /**
     * @param iterable<ApplicationBranchRuleInterface> $rules
     */
    public function __construct(iterable $rules)
    {
        $this->rules = is_array($rules) ? $rules : iterator_to_array($rules);
    }

    public function resolve(ApplicationBranchContext $context): ?int
    {
        foreach ($this->rules as $rule) {
            if (! $rule->supports($context)) {
                continue;
            }

            $branchId = $rule->resolveBranchId($context);
            if ($branchId !== null && $branchId > 0) {
                return $branchId;
            }
        }

        return null;
    }
}
