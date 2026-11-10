<?php

namespace App\Modules\HR\ApprovalPolicies\Models;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Observers\ApprovalPolicyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected $casts = [
        'application_type_id' => 'integer',
        'levels' => 'integer',
        'custom_approver_user_ids' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Normalize branch_ids so empty arrays are persisted as NULL (Global/All)
     * and non-empty arrays are always persisted as integer IDs.
     */
    protected function branchIds(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?array {
                if ($value === null || $value === '') {
                    return null;
                }

                $decoded = is_array($value) ? $value : json_decode((string) $value, true);
                if (! is_array($decoded)) {
                    return null;
                }

                $normalized = array_values(array_unique(array_filter(array_map('intval', $decoded))));

                return empty($normalized) ? null : $normalized;
            },
            set: function (mixed $value): ?string {
                if (! is_array($value) || empty($value)) {
                    return null;
                }

                $normalized = array_values(array_unique(array_filter(array_map('intval', $value))));

                return empty($normalized) ? null : json_encode($normalized);
            }
        );
    }

    public function scopeMatchingRecord(Builder $query, Model&ApprovableRecord $record): Builder
    {
        return $this->scopeActiveForCriteria(
            $query,
            $record::class,
            $record->approvalApplicationTypeId(),
            $record->approvalBranchId()
        );
    }

    public function scopeActiveForCriteria(
        Builder $query,
        string $approvableType,
        ?int $applicationTypeId,
        ?int $branchId
    ): Builder {
        return $query
            ->where('approvable_type', $approvableType)
            ->where('active', true)
            ->when(
                $applicationTypeId,
                fn (Builder $q) => $q->where(function (Builder $sub) use ($applicationTypeId) {
                    $sub->where('application_type_id', $applicationTypeId)
                        ->orWhereNull('application_type_id');
                }),
                fn (Builder $q) => $q->whereNull('application_type_id')
            )
            ->when(
                $branchId,
                fn (Builder $q) => $q->where(function (Builder $sub) use ($branchId) {
                    $sub->whereJsonContains('branch_ids', (int) $branchId)
                        ->orWhereJsonContains('branch_ids', (string) $branchId)
                        ->orWhereNull('branch_ids')
                        ->orWhereJsonLength('branch_ids', 0);
                }),
                fn (Builder $q) => $q->where(function (Builder $sub) {
                    $sub->whereNull('branch_ids')
                        ->orWhereJsonLength('branch_ids', 0);
                })
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
