<?php

namespace App\Modules\HR\ApprovalPolicies\Rules;

use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueApprovalPolicy implements ValidationRule
{
    protected ?int $ignoreId;
    protected ?string $approvableType;
    protected ?int $applicationTypeId;
    protected array $branchIds;

    public function __construct(
        mixed $ignoreId,
        mixed $approvableType,
        mixed $applicationTypeId,
        mixed $branchIds
    ) {
        $this->ignoreId = $ignoreId ? (int) $ignoreId : null;
        $this->approvableType = $approvableType ? (string) $approvableType : null;
        $this->applicationTypeId = ($applicationTypeId !== null && $applicationTypeId !== '' && $applicationTypeId !== 'all')
            ? (int) $applicationTypeId
            : null;
        $this->branchIds = ApprovalPolicy::normalizeBranchIds($branchIds) ?? [];
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->approvableType) {
            return;
        }

        $query = ApprovalPolicy::query()
            ->forSubject($this->approvableType, $this->applicationTypeId, includeWildcardType: false);

        if ($this->ignoreId) {
            $query->whereKeyNot($this->ignoreId);
        }

        $duplicates = $query->get(['id', 'name', 'branch_ids']);

        if ($duplicates->isEmpty()) {
            return;
        }

        foreach ($duplicates as $duplicate) {
            $duplicateBranches = ApprovalPolicy::normalizeBranchIds($duplicate->branch_ids) ?? [];

            $isBothGlobal = empty($this->branchIds) && empty($duplicateBranches);
            $hasBranchOverlap = ! empty($this->branchIds)
                && ! empty($duplicateBranches)
                && ! empty(array_intersect($this->branchIds, $duplicateBranches));

            if ($isBothGlobal || $hasBranchOverlap) {
                $fail(__('An approval policy for the same subject and branch(es) already exists.'));
                return;
            }
        }
    }
}
