<?php

namespace App\Modules\HR\ApprovalPolicies\Models;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role;

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
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

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
}
