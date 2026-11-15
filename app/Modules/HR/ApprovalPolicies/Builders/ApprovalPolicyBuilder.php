<?php

namespace App\Modules\HR\ApprovalPolicies\Builders;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Builder<ApprovalPolicy>
 */
class ApprovalPolicyBuilder extends Builder
{
    public function active(bool $state = true): self
    {
        return $this->where('active', $state);
    }

    public function forSubject(
        string $approvableType,
        ?int $applicationTypeId = null,
        bool $includeWildcardType = true
    ): self {
        return $this
            ->where('approvable_type', $approvableType)
            ->when(
                $applicationTypeId !== null,
                fn (self $q) => $includeWildcardType
                    ? $q->where(function (self $sub) use ($applicationTypeId) {
                        $sub->where('application_type_id', $applicationTypeId)
                            ->orWhereNull('application_type_id');
                    })
                    : $q->where('application_type_id', $applicationTypeId),
                fn (self $q) => $q->whereNull('application_type_id')
            );
    }

    /**
     * Scope policies that conflict with the given subject when validating uniqueness:
     * - For EmployeeApplicationV2 with null applicationTypeId ("All Employee Requests"),
     *   it conflicts with ANY policy for EmployeeApplicationV2 (wildcard or specific type).
     * - For EmployeeApplicationV2 with a specific applicationTypeId,
     *   it conflicts with policies for that specific type OR wildcard ("All Employee Requests").
     * - For other subjects (Overtime, AdvanceWage), it conflicts with policies of the same approvableType.
     */
    public function forConflictingSubject(
        string $approvableType,
        ?int $applicationTypeId = null
    ): self {
        return $this
            ->where('approvable_type', $approvableType)
            ->when(
                $approvableType === \App\Models\EmployeeApplicationV2::class,
                fn (self $q) => $applicationTypeId !== null
                    ? $q->where(function (self $sub) use ($applicationTypeId) {
                        $sub->where('application_type_id', $applicationTypeId)
                            ->orWhereNull('application_type_id');
                    })
                    : $q,
                fn (self $q) => $q->whereNull('application_type_id')
            );
    }

    public function forBranch(?int $branchId): self
    {
        return $this->when(
            $branchId,
            fn (self $q) => $q->where(function (self $sub) use ($branchId) {
                $sub->whereJsonContains('branch_ids', (int) $branchId)
                    ->orWhereJsonContains('branch_ids', (string) $branchId)
                    ->orWhereNull('branch_ids')
                    ->orWhereJsonLength('branch_ids', 0);
            }),
            fn (self $q) => $q->where(function (self $sub) {
                $sub->whereNull('branch_ids')
                    ->orWhereJsonLength('branch_ids', 0);
            })
        );
    }

    public function activeForCriteria(
        string $approvableType,
        ?int $applicationTypeId,
        ?int $branchId
    ): self {
        return $this
            ->active()
            ->forSubject($approvableType, $applicationTypeId, includeWildcardType: true)
            ->forBranch($branchId);
    }

    public function matchingRecord(Model&ApprovableRecord $record): self
    {
        return $this->activeForCriteria(
            $record::class,
            $record->approvalApplicationTypeId(),
            $record->approvalBranchId()
        );
    }

    public function orderedBySpecificity(): self
    {
        return $this
            ->orderByRaw('CASE WHEN branch_ids IS NULL OR JSON_LENGTH(branch_ids) = 0 THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN application_type_id IS NULL THEN 1 ELSE 0 END')
            ->latest('id');
    }
}
