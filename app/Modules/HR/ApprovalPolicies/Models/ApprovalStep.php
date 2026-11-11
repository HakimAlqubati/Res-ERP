<?php

namespace App\Modules\HR\ApprovalPolicies\Models;

use App\Models\Employee;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Builders\ApprovalStepBuilder;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role;

/**
 * @method static ApprovalStepBuilder query()
 */
class ApprovalStep extends Model
{
    protected $table = 'hr_approval_steps';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'approval_policy_id',
        'step_order',
        'approver_employee_id',
        'approver_user_id',
        'approver_role_id',
        'status',
        'can_be_skipped',
        'approved_at',
        'rejected_at',
        'notes',
    ];

    protected $casts = [
        'approval_policy_id' => 'integer',
        'step_order' => 'integer',
        'approver_employee_id' => 'integer',
        'approver_user_id' => 'integer',
        'approver_role_id' => 'integer',
        'can_be_skipped' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function newEloquentBuilder($query): ApprovalStepBuilder
    {
        return new ApprovalStepBuilder($query);
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(ApprovalPolicy::class, 'approval_policy_id');
    }

    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStepStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStepStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ApprovalStepStatus::REJECTED;
    }

    public function isSkipped(): bool
    {
        return $this->status === ApprovalStepStatus::SKIPPED;
    }

    public function markAsApproved(int $userId, ?int $employeeId = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => ApprovalStepStatus::APPROVED,
            'approver_user_id' => $userId,
            'approver_employee_id' => $this->approver_employee_id ?? $employeeId,
            'approved_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function markAsRejected(int $userId, ?int $employeeId = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => ApprovalStepStatus::REJECTED,
            'approver_user_id' => $userId,
            'approver_employee_id' => $this->approver_employee_id ?? $employeeId,
            'rejected_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function approverDisplayName(
        bool $includeRoleContext = false,
        string $rolePrefix = 'Any',
        string $fallback = 'Unknown'
    ): string {
        $this->loadMissing(['approverUser:id,name', 'approverEmployee:id,name', 'approverRole:id,name']);

        $personName = $this->approverEmployee?->name ?: $this->approverUser?->name;
        $roleName = $this->approverRole?->name;

        if ($includeRoleContext && $personName && $roleName) {
            return "{$personName} ({$roleName})";
        }

        if ($personName) {
            return $personName;
        }

        if ($roleName) {
            return "{$rolePrefix} {$roleName}";
        }

        if ($this->isSkipped()) {
            return 'Skipped (No Manager)';
        }

        return $this->approver_user_id ? "User #{$this->approver_user_id}" : $fallback;
    }
}
