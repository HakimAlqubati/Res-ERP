<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserBranchCannotBeChanged implements ValidationRule
{
    public function __construct(protected User $user) {}

    /**
     * Run the validation rule.
     *
     * Fails when the incoming branch_id differs from the current one
     * and the user already has a linked employee record.
     * Branch transfers must be done through the employee model.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $branchChanged = (int) $value !== (int) $this->user->getOriginal('branch_id');

        if ($branchChanged && $this->user->employee()->exists()) {
            $fail(__('Cannot change the branch directly on a user who has an employee record. Please transfer the employee to the new branch instead.'));
        }
    }
}
