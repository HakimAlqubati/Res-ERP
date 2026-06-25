<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers;

use App\Models\EmployeeOvertime;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\FinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowGuard;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeFinalApprovalHandler implements FinalApprovalHandler
{
    public function __construct(
        private readonly ApprovalWorkflowGuard $guard,
    ) {
    }

    public function handle(Model&ApprovableRecord $record, User $approvedBy): void
    {
        if (! $record instanceof EmployeeOvertime) {
            return;
        }

        $this->guard->withoutGuard(fn () => $record->update([
            'status' => EmployeeOvertime::STATUS_APPROVED,
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
        ]));
    }
}
