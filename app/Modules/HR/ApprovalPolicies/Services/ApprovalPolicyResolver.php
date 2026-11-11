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
            ->orderedBySpecificity()
            ->first();

        if (! $policy) {
            throw ApprovalWorkflowException::missingPolicy($record::class);
        }

        return $policy;
    }
}
