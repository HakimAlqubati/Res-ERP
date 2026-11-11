<?php

namespace App\Modules\HR\ApprovalPolicies\Models;

use App\Modules\HR\ApprovalPolicies\Builders\ApprovalPolicyBuilder;
use App\Modules\HR\ApprovalPolicies\Observers\ApprovalPolicyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static ApprovalPolicyBuilder query()
 */
#[ObservedBy([ApprovalPolicyObserver::class])]
class ApprovalPolicy extends Model
{
    use SoftDeletes;

    protected $table = 'hr_approval_policies';

    protected $fillable = [
        'name',
        'approvable_type',
        'application_type_id',
        'branch_ids',
        'mode',
        'levels',
        'custom_approver_user_ids',
        'active',
        'allow_skip_steps',
    ];

    protected $casts = [
        'application_type_id' => 'integer',
        'levels' => 'integer',
        'custom_approver_user_ids' => 'array',
        'active' => 'boolean',
        'allow_skip_steps' => 'boolean',
    ];

    public function newEloquentBuilder($query): ApprovalPolicyBuilder
    {
        return new ApprovalPolicyBuilder($query);
    }

    /**
     * Normalize branch_ids so empty arrays are returned as NULL (Global/All)
     * and non-empty arrays are always returned as unique positive integer IDs.
     *
     * @return list<int>|null
     */
    public static function normalizeBranchIds(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true);
        if (! is_array($decoded) || empty($decoded)) {
            return null;
        }

        $normalized = array_values(array_unique(array_filter(array_map('intval', $decoded))));

        return empty($normalized) ? null : $normalized;
    }

    protected function branchIds(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?array => self::normalizeBranchIds($value),
            set: function (mixed $value): ?string {
                $normalized = self::normalizeBranchIds($value);

                return $normalized === null ? null : json_encode($normalized);
            }
        );
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'approval_policy_id');
    }

    public function policySteps(): HasMany
    {
        return $this->hasMany(ApprovalPolicyStep::class, 'approval_policy_id')->orderBy('step_order');
    }
}
