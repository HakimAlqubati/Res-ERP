<?php

namespace App\Modules\HR\ApprovalPolicies\Traits;

use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowRequirementChecker;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowService;
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
