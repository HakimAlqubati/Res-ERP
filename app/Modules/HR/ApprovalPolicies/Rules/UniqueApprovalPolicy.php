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
        $this->branchIds = is_array($branchIds) ? $branchIds : [];
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
            ->where('approvable_type', $this->approvableType)
            ->when(
                $this->applicationTypeId !== null,
                fn ($q) => $q->where('application_type_id', $this->applicationTypeId),
                fn ($q) => $q->whereNull('application_type_id')
            );

        if ($this->ignoreId) {
            $query->whereKeyNot($this->ignoreId);
        }

        $duplicates = $query->get(['id', 'name', 'branch_ids']);

        if ($duplicates->isEmpty()) {
            return;
        }

        $currentBranches = array_values(array_unique(array_filter(array_map('intval', $this->branchIds))));

        foreach ($duplicates as $duplicate) {
            $duplicateBranches = array_values(array_unique(array_filter(array_map('intval', (array) $duplicate->branch_ids))));

            $isBothGlobal = empty($currentBranches) && empty($duplicateBranches);
            $hasBranchOverlap = ! empty($currentBranches)
                && ! empty($duplicateBranches)
                && ! empty(array_intersect($currentBranches, $duplicateBranches));

            if ($isBothGlobal || $hasBranchOverlap) {
                $fail(__('An approval policy for the same subject and branch(es) already exists.'));
                return;
            }
        }
    }
}
