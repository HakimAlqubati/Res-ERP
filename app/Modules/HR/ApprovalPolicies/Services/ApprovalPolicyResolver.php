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
        $policy = ApprovalPolicy::query()
            ->matchingRecord($record)
            ->orderByRaw('CASE WHEN branch_ids IS NULL OR JSON_LENGTH(branch_ids) = 0 THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN application_type_id IS NULL THEN 1 ELSE 0 END')
            ->latest('id')
            ->first();

        if (! $policy) {
            throw ApprovalWorkflowException::missingPolicy($record::class);
        }

        return $policy;
    }
}
