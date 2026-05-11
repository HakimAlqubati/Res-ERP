<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    public function __construct(
        private readonly ApprovalPolicyResolver $policyResolver,
        private readonly ApprovalApproverResolver $approverResolver,
        private readonly ApprovalStepCreator $stepCreator,
        private readonly FinalApprovalHandlerResolver $finalApprovalHandlerResolver,
        private readonly RejectionHandlerResolver $rejectionHandlerResolver,
        private readonly ApprovalWorkflowRequirementChecker $requirementChecker,
    ) {}

    public function createFor(Model&ApprovableRecord $record, bool $replaceExisting = false): void
    {
        DB::transaction(function () use ($record, $replaceExisting) {
            if ($record->approvalSteps()->exists()) {
                if (! $replaceExisting) {
                    return;
                }

                $record->approvalSteps()->delete();
            }

            $policy = $this->policyResolver->resolve($record);
            $approvers = $this->approverResolver->resolve($record, $policy);

            $this->stepCreator->create($record, $policy, $approvers);
        });
    }

    public function canUserApprove(Model&ApprovableRecord $record, User $user): bool
    {
        $this->ensureStepsExist($record);

        $step = $this->currentStep($record);

        return $step && (int) $step->approver_user_id === (int) $user->id;
    }

    /**
     * @throws AuthorizationException
     */
    public function assertUserCanApprove(Model&ApprovableRecord $record, User $user): ApprovalStep
    {
        $this->ensureStepsExist($record);

        $step = $this->currentStep($record);
        if (! $step) {
            throw new AuthorizationException('No pending approval step for this record.');
        }

        if ((int) $step->approver_user_id !== (int) $user->id) {
            throw new AuthorizationException($this->notCurrentApproverMessage($step));
        }

        return $step;
    }

    /**
     * @throws AuthorizationException
     */
    public function approve(Model&ApprovableRecord $record, User $user, ?string $notes = null): Model
    {
        return DB::transaction(function () use ($record, $user, $notes) {
            $this->ensureStepsExist($record);

            $step = $this->currentStepForUpdate($record);

            if (! $step) {
                throw new AuthorizationException('No pending approval step for this record.');
            }

            if ((int) $step->approver_user_id !== (int) $user->id) {
                throw new AuthorizationException($this->notCurrentApproverMessage($step));
            }

            $step->update([
                'status' => ApprovalStepStatus::APPROVED,
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            if (! $this->hasPendingSteps($record)) {
                $handler = $this->finalApprovalHandlerResolver->resolve($record);
                $handler?->handle($record, $user);
            }

            return $record->refresh();
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function reject(Model&ApprovableRecord $record, User $user, ?string $notes = null): Model
    {
        return DB::transaction(function () use ($record, $user, $notes) {
            $this->ensureStepsExist($record);

            $step = $this->currentStepForUpdate($record);

            if (! $step) {
                throw new AuthorizationException('No pending approval step for this record.');
            }

            if ((int) $step->approver_user_id !== (int) $user->id) {
                throw new AuthorizationException($this->notCurrentApproverMessage($step));
            }

            $step->update([
                'status' => ApprovalStepStatus::REJECTED,
                'rejected_at' => now(),
                'notes' => $notes,
            ]);

            $this->rejectionHandlerResolver->resolve($record)?->handle($record, $user, $notes);

            return $record->refresh();
        });
    }

    private function currentStep(Model&ApprovableRecord $record): ?ApprovalStep
    {
        return $record->approvalSteps()
            ->where('status', ApprovalStepStatus::PENDING)
            ->orderBy('step_order')
            ->first();
    }

    private function currentStepForUpdate(Model&ApprovableRecord $record): ?ApprovalStep
    {
        return ApprovalStep::query()
            ->with(['approverUser:id,name', 'approverEmployee:id,name'])
            ->where('approvable_type', $record::class)
            ->where('approvable_id', $record->getKey())
            ->where('status', ApprovalStepStatus::PENDING)
            ->orderBy('step_order')
            ->lockForUpdate()
            ->first();
    }

    private function hasPendingSteps(Model&ApprovableRecord $record): bool
    {
        return ApprovalStep::query()
            ->where('approvable_type', $record::class)
            ->where('approvable_id', $record->getKey())
            ->where('status', ApprovalStepStatus::PENDING)
            ->exists();
    }

    private function ensureStepsExist(Model&ApprovableRecord $record): void
    {
        if ($record->approvalSteps()->exists()) {
            return;
        }

        if ($this->requirementChecker->hasActivePolicy($record)) {
            $this->createFor($record);
        }
    }

    private function notCurrentApproverMessage(ApprovalStep $step): string
    {
        $step->loadMissing(['approverUser:id,name', 'approverEmployee:id,name']);

        $approverName = $step->approverEmployee?->name
            ?: $step->approverUser?->name
            ?: "User #{$step->approver_user_id}";

        return "Waiting for {$approverName} to approve step #{$step->step_order}.";
    }
}
