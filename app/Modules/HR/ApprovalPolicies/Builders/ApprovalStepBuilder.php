<?php

namespace App\Modules\HR\ApprovalPolicies\Builders;

use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Builder<ApprovalStep>
 */
class ApprovalStepBuilder extends Builder
{
    public function forApprovable(Model $record): self
    {
        return $this
            ->where('approvable_type', $record::class)
            ->where('approvable_id', $record->getKey());
    }

    /**
     * @param list<class-string<Model>> $types
     */
    public function forApprovableTypes(array $types): self
    {
        return $this->whereIn('approvable_type', $types);
    }

    public function pending(): self
    {
        return $this->where('status', ApprovalStepStatus::PENDING);
    }

    public function approved(): self
    {
        return $this->where('status', ApprovalStepStatus::APPROVED);
    }

    public function rejected(): self
    {
        return $this->where('status', ApprovalStepStatus::REJECTED);
    }

    public function skipped(): self
    {
        return $this->where('status', ApprovalStepStatus::SKIPPED);
    }

    public function inOrder(): self
    {
        return $this->orderBy('step_order');
    }

    public function preceding(int $stepOrder): self
    {
        return $this->where('step_order', '<', $stepOrder);
    }

    public function withApproverSummary(): self
    {
        return $this->with([
            'approverUser:id,name',
            'approverEmployee:id,name',
            'approverRole:id,name',
        ]);
    }

    public function roleAssigned(): self
    {
        return $this->whereNotNull('approver_role_id');
    }

    public function userAssigned(): self
    {
        return $this
            ->whereNull('approver_role_id')
            ->whereNotNull('approver_user_id');
    }

    public function markAsSkipped(?string $notes = null): int
    {
        $attributes = [
            'status' => ApprovalStepStatus::SKIPPED,
            'updated_at' => now(),
        ];

        if ($notes !== null) {
            $attributes['notes'] = $notes;
        }

        return $this->update($attributes);
    }

    public function resetForRollback(): void
    {
        $now = now();

        (clone $this)->roleAssigned()->update([
            'status' => ApprovalStepStatus::PENDING,
            'approver_user_id' => null,
            'approver_employee_id' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'notes' => null,
            'updated_at' => $now,
        ]);

        (clone $this)->userAssigned()->update([
            'status' => ApprovalStepStatus::PENDING,
            'approved_at' => null,
            'rejected_at' => null,
            'notes' => null,
            'updated_at' => $now,
        ]);
    }
}
