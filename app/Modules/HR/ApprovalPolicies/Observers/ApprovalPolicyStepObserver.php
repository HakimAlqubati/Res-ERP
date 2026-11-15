<?php

namespace App\Modules\HR\ApprovalPolicies\Observers;

use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicyStep;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalPolicySyncService;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowRequirementChecker;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowService;

class ApprovalPolicyStepObserver
{
    public function saved(ApprovalPolicyStep $step): void
    {
        $this->syncParentPolicy($step);
    }

    public function deleted(ApprovalPolicyStep $step): void
    {
        $this->syncParentPolicy($step);
    }

    private function syncParentPolicy(ApprovalPolicyStep $step): void
    {
        ApprovalWorkflowRequirementChecker::clearCache();
        ApprovalWorkflowService::clearCache();

        $policy = $step->policy()->first();

        if ($policy) {
            app(ApprovalPolicySyncService::class)->syncOnPolicyStepsChanged($policy);
        }
    }
}
