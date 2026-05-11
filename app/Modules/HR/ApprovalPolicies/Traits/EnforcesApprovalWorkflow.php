<?php

namespace App\Modules\HR\ApprovalPolicies\Traits;

use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowGuard;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowMessageBuilder;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowRequirementChecker;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

trait EnforcesApprovalWorkflow
{
    public static function bootEnforcesApprovalWorkflow(): void
    {
        static::saving(function (Model&ApprovableRecord $record): void {
            self::guardFinalApprovalMutation($record);
        });

        static::creating(function (Model&ApprovableRecord $record): void {
            self::guardFinalApprovalMutation($record);
        });
    }

    private static function guardFinalApprovalMutation(Model&ApprovableRecord $record): void
    {
        if (app(ApprovalWorkflowGuard::class)->isBypassed()) {
            return;
        }

        if (! self::isFinalApprovalMutation($record)) {
            return;
        }

        if (! app(ApprovalWorkflowRequirementChecker::class)->requiresWorkflow($record)) {
            return;
        }

        throw new AuthorizationException(
            app(ApprovalWorkflowMessageBuilder::class)->directApprovalBlocked($record)
        );
    }

    private static function isFinalApprovalMutation(Model $record): bool
    {
        $statusColumn = method_exists($record, 'approvalStatusColumn')
            ? $record->approvalStatusColumn()
            : 'status';

        $approvedStatuses = method_exists($record, 'approvalApprovedStatuses')
            ? $record->approvalApprovedStatuses()
            : ['approved'];

        if ($statusColumn && ($record->isDirty($statusColumn) || ! $record->exists)) {
            return in_array($record->getAttribute($statusColumn), $approvedStatuses, true);
        }

        foreach (['approved_by', 'approved_at'] as $column) {
            if (($record->isDirty($column) || ! $record->exists) && filled($record->getAttribute($column))) {
                return true;
            }
        }

        return false;
    }
}
