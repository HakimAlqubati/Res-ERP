<?php

namespace App\Modules\HR\ApprovalPolicies\Services;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalMode;
use App\Modules\HR\ApprovalPolicies\Enums\ApprovalStepStatus;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalPolicy;
use App\Modules\HR\ApprovalPolicies\Models\ApprovalStep;
use Illuminate\Database\Eloquent\Model;

class ApprovalPolicySyncService
{
    public function syncOnPolicySaved(ApprovalPolicy $policy): void
    {
        ApprovalWorkflowRequirementChecker::clearCache();
        ApprovalWorkflowService::clearCache();

        if ($policy->wasChanged('allow_skip_steps')) {
            $policy->steps()
                ->pending()
                ->update([
                    'can_be_skipped' => (bool) $policy->allow_skip_steps,
                ]);
        }

        if (! $policy->active) {
            return;
        }

        if ($policy->mode === ApprovalMode::CONFIGURED_STEPS && ! $policy->policySteps()->exists()) {
            return;
        }

        $shouldReplaceUnstarted = $policy->wasChanged(['mode', 'levels', 'custom_approver_user_ids']);

        if ($shouldReplaceUnstarted) {
            $this->rebuildUnstartedStepsForPolicy($policy);
        }

        $this->initializeMissingStepsForPendingRecords($policy);
    }

    public function syncOnPolicyStepsChanged(ApprovalPolicy $policy): void
    {
        ApprovalWorkflowRequirementChecker::clearCache();
        ApprovalWorkflowService::clearCache();

        if (! $policy->active) {
            return;
        }

        if ($policy->mode === ApprovalMode::CONFIGURED_STEPS && ! $policy->policySteps()->exists()) {
            return;
        }

        $policy->unsetRelation('policySteps');

        $this->rebuildUnstartedStepsForPolicy($policy);
        $this->initializeMissingStepsForPendingRecords($policy);
    }

    private function rebuildUnstartedStepsForPolicy(ApprovalPolicy $policy): void
    {
        $modelClass = $policy->approvable_type;

        if (! $modelClass || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return;
        }

        $approvableIds = ApprovalStep::query()
            ->where('approval_policy_id', $policy->id)
            ->where('approvable_type', $modelClass)
            ->select('approvable_id')
            ->distinct()
            ->pluck('approvable_id');

        if ($approvableIds->isEmpty()) {
            return;
        }

        $startedIds = ApprovalStep::query()
            ->where('approvable_type', $modelClass)
            ->whereIn('approvable_id', $approvableIds)
            ->whereIn('status', [ApprovalStepStatus::APPROVED, ApprovalStepStatus::REJECTED])
            ->select('approvable_id')
            ->distinct()
            ->pluck('approvable_id')
            ->all();

        $unstartedIds = $approvableIds->diff($startedIds)->values();

        if ($unstartedIds->isEmpty()) {
            return;
        }

        $records = $modelClass::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $unstartedIds)
            ->where('status', 'pending')
            ->get();

        foreach ($records as $record) {
            if ($record instanceof ApprovableRecord) {
                try {
                    $record->initializeApprovalWorkflowIfRequired(replaceExisting: true);
                } catch (\Throwable) {
                    // Ignore records with invalid approver configuration during background sync
                }
            }
        }
    }

    private function initializeMissingStepsForPendingRecords(ApprovalPolicy $policy): void
    {
        $modelClass = $policy->approvable_type;

        if (! $modelClass || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return;
        }

        $query = $modelClass::query()
            ->withoutGlobalScopes()
            ->where('status', 'pending')
            ->whereDoesntHave('approvalSteps');

        if ($policy->application_type_id !== null) {
            $query->where('application_type_id', $policy->application_type_id);
        }

        $branchIds = ApprovalPolicy::normalizeBranchIds($policy->branch_ids);
        if (! empty($branchIds)) {
            $query->whereIn('branch_id', $branchIds);
        }

        $records = $query->limit(200)->get();

        foreach ($records as $record) {
            if ($record instanceof ApprovableRecord) {
                try {
                    $record->initializeApprovalWorkflowIfRequired(replaceExisting: false);
                } catch (\Throwable) {
                    // Ignore records that cannot resolve approvers
                }
            }
        }
    }
}
