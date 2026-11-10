<?php

namespace App\Modules\HR\ApprovalPolicies\Rules;

use App\Models\AdvanceWage;
use App\Models\EmployeeApplicationV2;
use App\Models\EmployeeOvertime;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PolicyNotInUse implements ValidationRule
{
    public function __construct(protected ?ApprovalPolicy $policy = null)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $policy = $this->policy ?? ($value instanceof ApprovalPolicy ? $value : ApprovalPolicy::find($value));

        if (! $policy) {
            return;
        }

        $restrictedModels = [
            EmployeeApplicationV2::class,
            EmployeeOvertime::class,
            AdvanceWage::class,
        ];

        $isInUse = $policy->steps()
            ->whereIn('approvable_type', $restrictedModels)
            ->where('status', ApprovalStepStatus::PENDING)
            ->exists();

        if ($isInUse) {
            $fail(__('This policy is currently in use by pending applications and cannot be deleted.'));
        }
    }
}
