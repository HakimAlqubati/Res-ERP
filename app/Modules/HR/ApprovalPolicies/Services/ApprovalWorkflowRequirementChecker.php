<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflowRequirementChecker
{
    /**
     * Request-scoped cache for active policy existence checks to eliminate N+1 queries.
     *
     * @var array<string, bool>
     */
    private static array $activePolicyCache = [];

    public static function clearCache(): void
    {
        self::$activePolicyCache = [];
    }

    public function requiresWorkflow(Model&ApprovableRecord $record): bool
    {
        if ($record->exists) {
            if ($record->relationLoaded('approvalSteps')) {
                if ($record->approvalSteps->isNotEmpty()) {
                    return $record->approvalSteps->contains('status', ApprovalStepStatus::PENDING);
                }
            } elseif ($record->approvalSteps()->exists()) {
                return $record->approvalSteps()->pending()->exists();
            }
        }

        return $this->hasActivePolicy($record);
    }

    public function hasActivePolicy(Model&ApprovableRecord $record): bool
    {
        $cacheKey = implode(':', [
            $record::class,
            $record->approvalApplicationTypeId() ?? 'all',
            $record->approvalBranchId() ?? 'global',
        ]);

        if (! array_key_exists($cacheKey, self::$activePolicyCache)) {
            self::$activePolicyCache[$cacheKey] = ApprovalPolicy::query()
                ->matchingRecord($record)
                ->exists();
        }

        return self::$activePolicyCache[$cacheKey];
    }
}
