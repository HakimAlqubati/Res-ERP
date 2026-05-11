<?php

namespace App\Modules\HR\ApprovalPolicies\Models;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalPolicy extends Model
{
    protected $table = 'hr_approval_policies';

    protected $fillable = [
        'name',
        'approvable_type',
        'application_type_id',
        'branch_id',
        'mode',
        'levels',
        'custom_approver_user_ids',
        'active',
    ];

    protected $casts = [
        'application_type_id' => 'integer',
        'branch_id' => 'integer',
        'levels' => 'integer',
        'custom_approver_user_ids' => 'array',
        'active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'approval_policy_id');
    }

    public function policySteps(): HasMany
    {
        return $this->hasMany(ApprovalPolicyStep::class, 'approval_policy_id')->orderBy('step_order');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
