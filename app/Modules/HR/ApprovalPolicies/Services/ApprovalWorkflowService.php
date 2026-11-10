<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Models\Employee;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
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

        // 1. Reset role-based steps back to pending and clear the previous acting user/employee
        $record->approvalSteps()
            ->whereNotNull('approver_role_id')
            ->update([
                'status' => ApprovalStepStatus::PENDING,
                'approver_user_id' => null,
                'approver_employee_id' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'notes' => null,
            ]);

        // 2. Reset user-based steps back to pending while keeping their assigned approver_user_id & approver_employee_id
        $record->approvalSteps()
            ->whereNull('approver_role_id')
            ->whereNotNull('approver_user_id')
            ->update([
                'status' => ApprovalStepStatus::PENDING,
                'approved_at' => null,
                'rejected_at' => null,
                'notes' => null,
            ]);

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

        $step = $this->currentStep($record);

        return $step && $this->userCanApproveStep($step, $user);
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

        $step = $this->currentStep($record);
        if (! $step) {
            throw new AuthorizationException('No pending approval step for this record.');
        }

        if (! $this->userCanApproveStep($step, $user)) {
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
            if ($this->isSelfApproval($record, $user)) {
                throw new AuthorizationException(__('You cannot approve or reject your own request.'));
            }

            $this->ensureStepsExist($record);

            $step = $this->currentStepForUpdate($record);

            if (! $step) {
                throw new AuthorizationException('No pending approval step for this record.');
            }

            if (! $this->userCanApproveStep($step, $user)) {
                throw new AuthorizationException($this->notCurrentApproverMessage($step));
            }

            $step->update([
                'status' => ApprovalStepStatus::APPROVED,
                'approver_user_id' => $user->id,
                'approver_employee_id' => $step->approver_employee_id ?? $this->resolveUserEmployeeId($user),
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
            if ($this->isSelfApproval($record, $user)) {
                throw new AuthorizationException(__('You cannot approve or reject your own request.'));
            }

            $this->ensureStepsExist($record);

            $step = $this->currentStepForUpdate($record);

            if (! $step) {
                throw new AuthorizationException('No pending approval step for this record.');
            }

            if (! $this->userCanApproveStep($step, $user)) {
                throw new AuthorizationException($this->notCurrentApproverMessage($step));
            }

            $step->update([
                'status' => ApprovalStepStatus::REJECTED,
                'approver_user_id' => $user->id,
                'approver_employee_id' => $step->approver_employee_id ?? $this->resolveUserEmployeeId($user),
                'rejected_at' => now(),
                'notes' => $notes,
            ]);

            // Mark any remaining subsequent pending steps as skipped so no step stays pending after rejection
            ApprovalStep::query()
                ->where('approvable_type', $record::class)
                ->where('approvable_id', $record->getKey())
                ->where('status', ApprovalStepStatus::PENDING)
                ->update([
                    'status' => ApprovalStepStatus::SKIPPED,
                    'updated_at' => now(),
                ]);

            $this->rejectionHandlerResolver->resolve($record)?->handle($record, $user, $notes);

            return $record->refresh();
        });
    }

    private function currentStep(Model&ApprovableRecord $record): ?ApprovalStep
    {
        if ($record->relationLoaded('currentApprovalStep')) {
            return $record->getRelation('currentApprovalStep');
        }

        if ($record->relationLoaded('approvalSteps')) {
            return $record->approvalSteps
                ->where('status', ApprovalStepStatus::PENDING)
                ->sortBy('step_order')
                ->first();
        }

        return $record->approvalSteps()
            ->where('status', ApprovalStepStatus::PENDING)
            ->orderBy('step_order')
            ->first();
    }

    private function currentStepForUpdate(Model&ApprovableRecord $record): ?ApprovalStep
    {
        return ApprovalStep::query()
            ->with(['approverUser:id,name', 'approverEmployee:id,name', 'approverRole:id,name'])
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
        $step->loadMissing(['approverUser:id,name', 'approverEmployee:id,name', 'approverRole:id,name']);

        $approverName = $step->approverEmployee?->name
            ?: $step->approverUser?->name
            ?: ($step->approverRole?->name ? "any {$step->approverRole->name}" : null)
            ?: "User #{$step->approver_user_id}";

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
