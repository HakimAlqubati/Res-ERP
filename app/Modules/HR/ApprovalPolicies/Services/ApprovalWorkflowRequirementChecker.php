<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflowRequirementChecker
{
    public function requiresWorkflow(Model&ApprovableRecord $record): bool
    {
        if ($record->exists && $record->approvalSteps()
            ->where('status', ApprovalStepStatus::PENDING)
            ->exists()) {
            return true;
        }

        return $this->hasActivePolicy($record);
    }

    public function hasActivePolicy(Model&ApprovableRecord $record): bool
    {
        return ApprovalPolicy::query()
            ->where('approvable_type', $record::class)
            ->where('active', true)
            ->when(
                $record->approvalApplicationTypeId(),
                fn ($query, int $typeId) => $query->where(function ($q) use ($typeId) {
                    $q->where('application_type_id', $typeId)
                        ->orWhereNull('application_type_id');
                }),
                fn ($query) => $query->whereNull('application_type_id')
            )
            ->when(
                $record->approvalBranchId(),
                fn ($query, int $branchId) => $query->where(function ($q) use ($branchId) {
                    $q->whereJsonContains('branch_ids', $branchId)
                        ->orWhereNull('branch_ids');
                }),
                fn ($query) => $query->whereNull('branch_ids')
            )
            ->exists();
    }
}
