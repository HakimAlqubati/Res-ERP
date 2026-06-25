<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflowMessageBuilder
{
    public function directApprovalBlocked(Model&ApprovableRecord $record): string
    {

        $currentStep = $record->approvalSteps()
            ->with(['approverUser:id,name', 'approverEmployee:id,name', 'approverRole:id,name'])
            ->where('status', ApprovalStepStatus::PENDING)
            ->orderBy('step_order')
            ->first();

        if (! $currentStep) {
            return 'This record has an active approval policy, but its approval steps have not been initialized yet. Please run the approval action again so the workflow can initialize the current approver.';
        }

        $approverName = $currentStep->approverEmployee?->name
            ?: $currentStep->approverUser?->name
            ?: ($currentStep->approverRole?->name ? "any {$currentStep->approverRole->name}" : null)
            ?: "User #{$currentStep->approver_user_id}";

        return "This record is waiting for approval step #{$currentStep->step_order}. The current approver is {$approverName}. Please approve it through the approval workflow.";
    }
}
