<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers;

use App\Models\AdvanceWage;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\FinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowGuard;
use Illuminate\Database\Eloquent\Model;

class AdvanceWageFinalApprovalHandler implements FinalApprovalHandler
{
    public function __construct(
        private readonly ApprovalWorkflowGuard $guard,
    ) {
    }

    public function handle(Model&ApprovableRecord $record, User $approvedBy): void
    {
        if (! $record instanceof AdvanceWage) {
            return;
        }

        $this->guard->withoutGuard(fn () => $record->update([
            'status' => AdvanceWage::STATUS_SETTLED,
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
        ]));
    }
}
