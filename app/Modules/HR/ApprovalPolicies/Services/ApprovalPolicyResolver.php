<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Exceptions\ApprovalWorkflowException;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Illuminate\Database\Eloquent\Model;

class ApprovalPolicyResolver
{
    public function resolve(Model&ApprovableRecord $record): ApprovalPolicy
    {
        $branchId = $record->approvalBranchId();
        $applicationTypeId = $record->approvalApplicationTypeId();

        $policy = ApprovalPolicy::query()
            ->where('approvable_type', $record::class)
            ->where('active', true)
            ->when(
                $applicationTypeId,
                fn ($query) => $query->where(function ($q) use ($applicationTypeId) {
                    $q->where('application_type_id', $applicationTypeId)
                        ->orWhereNull('application_type_id');
                }),
                fn ($query) => $query->whereNull('application_type_id')
            )
            ->when(
                $branchId,
                fn ($query) => $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                }),
                fn ($query) => $query->whereNull('branch_id')
            )
            ->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN application_type_id IS NULL THEN 1 ELSE 0 END')
            ->latest('id')
            ->first();

        if (! $policy) {
            throw ApprovalWorkflowException::missingPolicy($record::class);
        }

        return $policy;
    }
}
