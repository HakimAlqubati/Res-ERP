<?php

namespace App\Modules\HR\ApprovalPolicies\Rules;

use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Support\ApprovalSubjectRegistry;
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

        $isInUse = $policy->steps()
            ->forApprovableTypes(ApprovalSubjectRegistry::restrictedModels())
            ->pending()
            ->exists();

        if ($isInUse) {
            $fail(__('This policy is currently in use by pending applications and cannot be deleted.'));
        }
    }
}
