<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\DTOs\ApprovalApprover;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApprovalStepCreator
{
    /**
     * @param Collection<int, ApprovalApprover> $approvers
     */
    public function create(Model&ApprovableRecord $record, ApprovalPolicy $policy, Collection $approvers): void
    {
        $rows = $approvers
            ->values()
            ->map(fn (ApprovalApprover $approver, int $index) => $approver->toStepAttributes($record, $policy, $index + 1))
            ->all();

        ApprovalStep::query()->insert($rows);
    }
}
