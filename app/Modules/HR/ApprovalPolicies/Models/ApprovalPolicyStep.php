<?php

namespace App\Modules\HR\ApprovalPolicies\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalPolicyStep extends Model
{
    protected $table = 'hr_approval_policy_steps';

    protected $fillable = [
        'approval_policy_id',
        'step_order',
        'approver_type',
        'approver_user_id',
        'manager_level',
    ];

    protected $casts = [
        'approval_policy_id' => 'integer',
        'step_order' => 'integer',
        'approver_user_id' => 'integer',
        'manager_level' => 'integer',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(ApprovalPolicy::class, 'approval_policy_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
