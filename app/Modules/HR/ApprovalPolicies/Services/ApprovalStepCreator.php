<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\DTOs\ApprovalApprover;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
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
            ->map(fn (ApprovalApprover $approver, int $index) => [
                'approvable_type' => $record::class,
                'approvable_id' => $record->getKey(),
                'approval_policy_id' => $policy->id,
                'step_order' => $index + 1,
                'approver_employee_id' => $approver->employeeId,
                'approver_user_id' => $approver->userId,
                'approver_role_id' => $approver->roleId,
                'status' => ApprovalStepStatus::PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        ApprovalStep::query()->insert($rows);
    }
}
