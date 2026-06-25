<?php

namespace App\Modules\HR\ApprovalPolicies\Traits;

use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasApprovalWorkflow
{
    public function approvalSteps(): MorphMany
    {
        return $this->morphMany(ApprovalStep::class, 'approvable')->orderBy('step_order');
    }

    public function currentApprovalStep(): MorphOne
    {
        return $this->morphOne(ApprovalStep::class, 'approvable')
            ->where('status', ApprovalStepStatus::PENDING)
            ->orderBy('step_order');
    }
}
