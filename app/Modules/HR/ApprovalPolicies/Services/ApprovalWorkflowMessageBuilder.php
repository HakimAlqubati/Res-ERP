<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflowMessageBuilder
{
    public function directApprovalBlocked(Model&ApprovableRecord $record): string
    {
        $currentStep = $record->approvalSteps()
            ->withApproverSummary()
            ->pending()
            ->inOrder()
            ->first();

        if (! $currentStep) {
            return 'This record has an active approval policy, but its approval steps have not been initialized yet. Please run the approval action again so the workflow can initialize the current approver.';
        }

        $approverName = $currentStep->approverDisplayName(rolePrefix: 'any');

        return "This record is waiting for approval step #{$currentStep->step_order}. The current approver is {$approverName}";
    }
}
