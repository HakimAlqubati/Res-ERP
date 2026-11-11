<?php

namespace App\Modules\HR\ApprovalPolicies\Observers;

use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Rules\PolicyNotInUse;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowRequirementChecker;
use Illuminate\Validation\ValidationException;

class ApprovalPolicyObserver
{
    /**
     * Handle the ApprovalPolicy "saved" event.
     */
    public function saved(ApprovalPolicy $approvalPolicy): void
    {
        ApprovalWorkflowRequirementChecker::clearCache();

        if ($approvalPolicy->wasChanged('allow_skip_steps')) {
            $approvalPolicy->steps()
                ->where('status', ApprovalStepStatus::PENDING)
                ->update([
                    'can_be_skipped' => (bool) $approvalPolicy->allow_skip_steps,
                ]);
        }
    }

    /**
     * Handle the ApprovalPolicy "deleting" event.
     */
    public function deleting(ApprovalPolicy $approvalPolicy): void
    {
        $this->ensurePolicyNotInUse($approvalPolicy);
    }

    /**
     * Handle the ApprovalPolicy "deleted" event.
     */
    public function deleted(ApprovalPolicy $approvalPolicy): void
    {
        ApprovalWorkflowRequirementChecker::clearCache();
    }

    /**
     * Handle the ApprovalPolicy "restored" event.
     */
    public function restored(ApprovalPolicy $approvalPolicy): void
    {
        ApprovalWorkflowRequirementChecker::clearCache();
    }

    /**
     * Ensure the policy does not have pending steps before deletion.
     *
     * @param ApprovalPolicy $approvalPolicy
     * @throws ValidationException
     */
    private function ensurePolicyNotInUse(ApprovalPolicy $approvalPolicy): void
    {
        $rule = new PolicyNotInUse($approvalPolicy);

        $rule->validate('policy', $approvalPolicy, function ($message) {
            throw ValidationException::withMessages([
                'error' => $message,
            ]);
        });
    }
}
