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
        $this->applicationTypeId = $applicationTypeId ? (int) $applicationTypeId : null;
        $this->branchIds = is_array($branchIds) ? $branchIds : [];
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = (string) $value;

        // Find policies with the same name, approvable type, and application type
        $query = ApprovalPolicy::query()
            ->where('name', $name)
            ->where('approvable_type', $this->approvableType)
            ->where('application_type_id', $this->applicationTypeId);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        $duplicates = $query->get();

        $currentBranches = array_map('strval', $this->branchIds);
        sort($currentBranches);

        foreach ($duplicates as $duplicate) {
            $duplicateBranches = is_array($duplicate->branch_ids) ? $duplicate->branch_ids : [];
            $duplicateBranches = array_map('strval', $duplicateBranches);
            sort($duplicateBranches);

            // If the selected branches perfectly match an existing policy's branches
            if ($duplicateBranches === $currentBranches) {
                $fail(__('An approval policy with the exact same name, subject, and branches already exists.'));
                return;
            }
        }
    }
}
