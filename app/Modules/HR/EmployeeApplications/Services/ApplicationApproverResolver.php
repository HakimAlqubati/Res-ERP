<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeApplications\Services;

use App\Models\EmployeeApplicationV2;
use App\Models\User;
use App\Modules\HR\EmployeeApplications\Contracts\ApplicationApproverRuleInterface;

/**
 * Resolves which manager User should receive the notification for an application.
 */
final class ApplicationApproverResolver
{
    /** @var array<int, ApplicationApproverRuleInterface> */
    private array $rules;

    /**
     * @param iterable<ApplicationApproverRuleInterface> $rules
     */
    public function __construct(iterable $rules)
    {
        $this->rules = is_array($rules) ? $rules : iterator_to_array($rules);
    }

    public function resolve(EmployeeApplicationV2 $application): ?User
    {
        foreach ($this->rules as $rule) {
            if (! $rule->supports($application)) {
                continue;
            }

            $recipient = $rule->resolveRecipient($application);
            if ($recipient !== null) {
                return $recipient;
            }
        }

        return null;
    }
}
