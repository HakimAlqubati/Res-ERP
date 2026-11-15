<?php

namespace App\Modules\HR\ApprovalPolicies\Traits;

use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowRequirementChecker;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasApprovalWorkflow
{
    public function approvalSteps(): MorphMany
    {
        return $this->morphMany(ApprovalStep::class, 'approvable')->inOrder();
    }

    public function currentApprovalStep(): MorphOne
    {
        return $this->morphOne(ApprovalStep::class, 'approvable')
            ->pending()
            ->inOrder();
    }

    public function scopeWhereAssignedApprover(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('approvalSteps', function ($stepQuery) use ($user) {
            $stepQuery->assignedToUser($user);
        });
    }

    public function hasAssignedApprover(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('approvalSteps')) {
            $roleIds = app(ApprovalWorkflowService::class)->getRoleIdsForUser($user);

            return $this->approvalSteps->contains(function (ApprovalStep $step) use ($user, $roleIds): bool {
                if ($step->approver_user_id && (int) $step->approver_user_id === (int) $user->id) {
                    return true;
                }

                return $step->approver_role_id && in_array((int) $step->approver_role_id, $roleIds, true);
            });
        }

        return $this->approvalSteps()->assignedToUser($user)->exists();
    }

    public function initializeApprovalWorkflowIfRequired(bool $replaceExisting = false): void
    {
        if (app(ApprovalWorkflowRequirementChecker::class)->hasActivePolicy($this)) {
            app(ApprovalWorkflowService::class)->createFor($this, $replaceExisting);
        }
    }

    public function requiresApprovalWorkflow(): bool
    {
        return app(ApprovalWorkflowRequirementChecker::class)->requiresWorkflow($this);
    }

    public function isSelfApprovalBy(?User $user): bool
    {
        return $user
            ? app(ApprovalWorkflowService::class)->isSelfApproval($this, $user)
            : false;
    }

    public function canBeApprovedBy(?User $user): bool
    {
        return $user
            ? app(ApprovalWorkflowService::class)->canUserApprove($this, $user)
            : false;
    }

    public function approveViaWorkflow(User $user, ?string $notes = null): static
    {
        /** @var static $updated */
        $updated = app(ApprovalWorkflowService::class)->approve($this, $user, $notes);

        return $updated;
    }

    public function rejectViaWorkflow(User $user, ?string $notes = null): static
    {
        /** @var static $updated */
        $updated = app(ApprovalWorkflowService::class)->reject($this, $user, $notes);

        return $updated;
    }

    public function resetApprovalWorkflowOnRollback(): void
    {
        app(ApprovalWorkflowService::class)->resetStepsOnRollback($this);
    }
}
