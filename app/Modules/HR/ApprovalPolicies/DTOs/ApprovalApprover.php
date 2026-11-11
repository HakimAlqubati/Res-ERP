<?php

namespace App\Modules\HR\ApprovalPolicies\DTOs;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use Illuminate\Database\Eloquent\Model;

final class ApprovalApprover
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly ?int $employeeId = null,
        public readonly ?int $roleId = null,
        public readonly bool $isSkipped = false,
    ) {
    }

    public static function forUser(int $userId, ?int $employeeId = null): self
    {
        return new self(userId: $userId, employeeId: $employeeId);
    }

    public static function forRole(int $roleId): self
    {
        return new self(roleId: $roleId);
    }

    public static function skipped(): self
    {
        return new self(isSkipped: true);
    }

    public function isValid(): bool
    {
        return $this->isSkipped || $this->userId !== null || $this->roleId !== null;
    }

    public function uniqueKey(): string
    {
        if ($this->isSkipped) {
            return 'skipped:' . spl_object_id($this);
        }

        return $this->roleId
            ? "role:{$this->roleId}"
            : "user:{$this->userId}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toStepAttributes(
        Model&ApprovableRecord $record,
        ApprovalPolicy $policy,
        int $stepOrder
    ): array {
        $now = now();

        return [
            'approvable_type' => $record::class,
            'approvable_id' => $record->getKey(),
            'approval_policy_id' => $policy->id,
            'step_order' => $stepOrder,
            'approver_employee_id' => $this->employeeId,
            'approver_user_id' => $this->userId,
            'approver_role_id' => $this->roleId,
            'status' => $this->isSkipped
                ? ApprovalStepStatus::SKIPPED
                : ApprovalStepStatus::PENDING,
            'can_be_skipped' => (bool) $policy->allow_skip_steps,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
