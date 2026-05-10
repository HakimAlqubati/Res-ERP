<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\DTOs\ApprovalApprover;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use App\Modules\HR\ApprovalPolicies\Exceptions\ApprovalWorkflowException;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApprovalApproverResolver
{
    /**
     * @return Collection<int, ApprovalApprover>
     */
    public function resolve(Model&ApprovableRecord $record, ApprovalPolicy $policy): Collection
    {
        $approvers = match ($policy->mode) {
            ApprovalMode::DIRECT_MANAGER => $this->directManager($record),
            ApprovalMode::BRANCH_MANAGER => $this->branchManager($record),
            ApprovalMode::MANAGER_CHAIN => $this->managerChain($record, $policy->levels),
            ApprovalMode::CUSTOM_USERS => $this->customUsers($policy),
            default => collect(),
        };

        $approvers = $approvers
            ->filter(fn (ApprovalApprover $approver) => $approver->userId > 0)
            ->unique('userId')
            ->values();

        if ($approvers->isEmpty()) {
            throw ApprovalWorkflowException::missingApprovers();
        }

        return $approvers;
    }

    private function directManager(Model&ApprovableRecord $record): Collection
    {
        $manager = $record->approvalEmployee()?->manager;

        if (! $manager?->user_id) {
            return collect();
        }

        return collect([new ApprovalApprover((int) $manager->user_id, (int) $manager->id)]);
    }

    private function branchManager(Model&ApprovableRecord $record): Collection
    {
        $branchId = $record->approvalBranchId();

        if (! $branchId) {
            return collect();
        }

        $managerUserId = Branch::query()
            ->whereKey($branchId)
            ->value('manager_id');

        if (! $managerUserId) {
            return collect();
        }

        return collect([new ApprovalApprover((int) $managerUserId)]);
    }

    private function managerChain(Model&ApprovableRecord $record, ?int $levels): Collection
    {
        $employee = $record->approvalEmployee();

        if (! $employee?->manager_id) {
            return collect();
        }

        $chain = collect();
        $visited = [];
        $managerId = (int) $employee->manager_id;

        while ($managerId > 0) {
            if (isset($visited[$managerId])) {
                throw ApprovalWorkflowException::circularManagerChain();
            }

            $visited[$managerId] = true;

            $manager = Employee::query()
                ->select(['id', 'manager_id', 'user_id'])
                ->find($managerId);

            if (! $manager) {
                break;
            }

            if ($manager->user_id) {
                $chain->push(new ApprovalApprover((int) $manager->user_id, (int) $manager->id));
            }

            if ($levels && $chain->count() >= $levels) {
                break;
            }

            $managerId = (int) $manager->manager_id;
        }

        return $chain;
    }

    private function customUsers(ApprovalPolicy $policy): Collection
    {
        $userIds = collect($policy->custom_approver_user_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        $existingUserIds = User::query()
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        return $userIds
            ->filter(fn (int $userId) => $existingUserIds->has($userId))
            ->map(fn (int $userId) => new ApprovalApprover($userId))
            ->values();
    }
}
