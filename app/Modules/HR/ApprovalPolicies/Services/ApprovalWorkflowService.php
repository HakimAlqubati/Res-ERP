<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Models\Employee;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    /**
     * Request-scoped cache for user role IDs.
     *
     * @var array<int, list<int>>
     */
    private static array $userRoleIdsCache = [];

    /**
     * Request-scoped cache for user employee IDs.
     *
     * @var array<int, ?int>
     */
    private static array $userEmployeeIdCache = [];

    public function __construct(
        private readonly ApprovalPolicyResolver $policyResolver,
        private readonly ApprovalApproverResolver $approverResolver,
        private readonly ApprovalStepCreator $stepCreator,
        private readonly FinalApprovalHandlerResolver $finalApprovalHandlerResolver,
        private readonly RejectionHandlerResolver $rejectionHandlerResolver,
        private readonly ApprovalWorkflowRequirementChecker $requirementChecker,
    ) {}

    public static function clearCache(): void
    {
        self::$userRoleIdsCache = [];
        self::$userEmployeeIdCache = [];
    }

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

            $record->unsetRelation('approvalSteps');
            $record->unsetRelation('currentApprovalStep');
        });
    }

    public function resetStepsOnRollback(Model&ApprovableRecord $record): void
    {
        if (! $record->approvalSteps()->exists()) {
            if ($this->requirementChecker->hasActivePolicy($record)) {
                $this->createFor($record);
            }

            return;
        }

        $record->approvalSteps()->resetForRollback();

        $record->unsetRelation('approvalSteps');
        $record->unsetRelation('currentApprovalStep');
    }

    public function isSelfApproval(Model&ApprovableRecord $record, User $user): bool
    {
        $userEmployeeId = $this->resolveUserEmployeeId($user);

        if ($userEmployeeId && isset($record->employee_id) && (int) $record->employee_id === $userEmployeeId) {
            return true;
        }

        $recordEmployee = $record->relationLoaded('employee')
            ? $record->getRelation('employee')
            : $record->approvalEmployee();

        if ($recordEmployee && $recordEmployee->user_id && (int) $recordEmployee->user_id === (int) $user->id) {
            return true;
        }

        if ($userEmployeeId && $recordEmployee && (int) $recordEmployee->id === $userEmployeeId) {
            return true;
        }

        return false;
    }

    public function canUserApprove(Model&ApprovableRecord $record, User $user): bool
    {
        if ($this->isSelfApproval($record, $user)) {
            return false;
        }

        $this->ensureStepsExist($record);

        return $this->resolveActionableStepForUser($this->pendingSteps($record), $user) !== null;
    }

    /**
     * @throws AuthorizationException
     */
    public function assertUserCanApprove(Model&ApprovableRecord $record, User $user): ApprovalStep
    {
        if ($this->isSelfApproval($record, $user)) {
            throw new AuthorizationException(__('You cannot approve or reject your own request.'));
        }

        $this->ensureStepsExist($record);

        $pendingSteps = $this->pendingSteps($record);
        if ($pendingSteps->isEmpty()) {
            throw new AuthorizationException('No pending approval step for this record.');
        }

        $step = $this->resolveActionableStepForUser($pendingSteps, $user);
        if (! $step) {
            throw new AuthorizationException($this->notCurrentApproverMessage($pendingSteps->first()));
        }

        return $step;
    }

    /**
     * @throws AuthorizationException
     */
    public function approve(Model&ApprovableRecord $record, User $user, ?string $notes = null): Model
    {
        return DB::transaction(function () use ($record, $user, $notes) {
            if ($this->isSelfApproval($record, $user)) {
                throw new AuthorizationException(__('You cannot approve or reject your own request.'));
            }

            $this->ensureStepsExist($record);

            $pendingSteps = $this->pendingStepsForUpdate($record);

            if ($pendingSteps->isEmpty()) {
                throw new AuthorizationException('No pending approval step for this record.');
            }

            $step = $this->resolveActionableStepForUser($pendingSteps, $user);

            if (! $step) {
                throw new AuthorizationException($this->notCurrentApproverMessage($pendingSteps->first()));
            }

            if ($pendingSteps->first()->step_order < $step->step_order) {
                ApprovalStep::query()
                    ->forApprovable($record)
                    ->pending()
                    ->preceding($step->step_order)
                    ->markAsSkipped("Skipped: Approved by step #{$step->step_order} ({$user->name})");
            }

            $step->markAsApproved($user->id, $this->resolveUserEmployeeId($user), $notes);

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
            if ($this->isSelfApproval($record, $user)) {
                throw new AuthorizationException(__('You cannot approve or reject your own request.'));
            }

            $this->ensureStepsExist($record);

            $pendingSteps = $this->pendingStepsForUpdate($record);

            if ($pendingSteps->isEmpty()) {
                throw new AuthorizationException('No pending approval step for this record.');
            }

            $step = $this->resolveActionableStepForUser($pendingSteps, $user);

            if (! $step) {
                throw new AuthorizationException($this->notCurrentApproverMessage($pendingSteps->first()));
            }

            $step->markAsRejected($user->id, $this->resolveUserEmployeeId($user), $notes);

            // Mark any remaining pending steps (preceding or subsequent) as skipped so no step stays pending after rejection
            ApprovalStep::query()
                ->forApprovable($record)
                ->pending()
                ->markAsSkipped();

            $this->rejectionHandlerResolver->resolve($record)?->handle($record, $user, $notes);

            return $record->refresh();
        });
    }

    /**
     * @return Collection<int, ApprovalStep>
     */
    private function pendingSteps(Model&ApprovableRecord $record): Collection
    {
        if ($record->relationLoaded('approvalSteps')) {
            return $record->approvalSteps
                ->where('status', ApprovalStepStatus::PENDING)
                ->sortBy('step_order')
                ->values();
        }

        return $record->approvalSteps()
            ->pending()
            ->inOrder()
            ->get();
    }

    /**
     * @return Collection<int, ApprovalStep>
     */
    private function pendingStepsForUpdate(Model&ApprovableRecord $record): Collection
    {
        return ApprovalStep::query()
            ->withApproverSummary()
            ->forApprovable($record)
            ->pending()
            ->inOrder()
            ->lockForUpdate()
            ->get();
    }

    /**
     * Return all pending steps that are currently actionable in sequence:
     * the first pending step is always actionable, and each subsequent pending step
     * is actionable if all preceding pending steps have `can_be_skipped = true`.
     *
     * @param Collection<int, ApprovalStep> $pendingSteps
     * @return Collection<int, ApprovalStep>
     */
    private function actionableSteps(Collection $pendingSteps): Collection
    {
        $actionable = collect();

        foreach ($pendingSteps as $step) {
            $actionable->push($step);

            if (! $step->can_be_skipped) {
                break;
            }
        }

        return $actionable;
    }

    /**
     * @param Collection<int, ApprovalStep> $pendingSteps
     */
    private function resolveActionableStepForUser(Collection $pendingSteps, User $user): ?ApprovalStep
    {
        return $this->actionableSteps($pendingSteps)
            ->first(fn (ApprovalStep $step) => $this->userCanApproveStep($step, $user));
    }

    private function hasPendingSteps(Model&ApprovableRecord $record): bool
    {
        return ApprovalStep::query()
            ->forApprovable($record)
            ->pending()
            ->exists();
    }

    private function ensureStepsExist(Model&ApprovableRecord $record): void
    {
        if ($record->relationLoaded('approvalSteps') && $record->approvalSteps->isNotEmpty()) {
            return;
        }

        if ($record->approvalSteps()->exists()) {
            return;
        }

        if ($this->requirementChecker->hasActivePolicy($record)) {
            $this->createFor($record);
        }
    }

    private function notCurrentApproverMessage(ApprovalStep $step): string
    {
        $approverName = $step->approverDisplayName(rolePrefix: 'any');

        return "Waiting for {$approverName} to approve step #{$step->step_order}.";
    }

    private function userCanApproveStep(ApprovalStep $step, User $user): bool
    {
        if ($step->approver_user_id && (int) $step->approver_user_id === (int) $user->id) {
            return true;
        }

        if (! $step->approver_role_id) {
            return false;
        }

        return in_array((int) $step->approver_role_id, $this->resolveUserRoleIds($user), true);
    }

    /**
     * @return list<int>
     */
    private function resolveUserRoleIds(User $user): array
    {
        $userId = (int) $user->id;

        if (! array_key_exists($userId, self::$userRoleIdsCache)) {
            self::$userRoleIdsCache[$userId] = $user->relationLoaded('roles')
                ? $user->roles->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                : $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return self::$userRoleIdsCache[$userId];
    }

    private function resolveUserEmployeeId(User $user): ?int
    {
        $userId = (int) $user->id;

        if (! array_key_exists($userId, self::$userEmployeeIdCache)) {
            if ($user->relationLoaded('employee')) {
                $empId = $user->getRelation('employee')?->id;
            } else {
                $empId = Employee::query()
                    ->withoutGlobalScopes()
                    ->where('user_id', $userId)
                    ->value('id');
            }

            self::$userEmployeeIdCache[$userId] = $empId ? (int) $empId : null;
        }

        return self::$userEmployeeIdCache[$userId];
    }
}
