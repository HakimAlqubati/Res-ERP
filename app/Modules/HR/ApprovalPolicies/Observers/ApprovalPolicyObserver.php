<?php

namespace App\Modules\HR\ApprovalPolicies\Observers;

use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Rules\PolicyNotInUse;
use Illuminate\Validation\ValidationException;

class ApprovalPolicyObserver
{
    /**
     * Handle the ApprovalPolicy "updating" event.
     */
    public function updating(ApprovalPolicy $approvalPolicy): void
    {
        $this->ensurePolicyNotInUse($approvalPolicy);
    }

    /**
     * Handle the ApprovalPolicy "deleting" event.
     */
    public function deleting(ApprovalPolicy $approvalPolicy): void
    {
        $this->ensurePolicyNotInUse($approvalPolicy);
    }

    /**
     * Ensure the policy is not in use by restricted models.
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
