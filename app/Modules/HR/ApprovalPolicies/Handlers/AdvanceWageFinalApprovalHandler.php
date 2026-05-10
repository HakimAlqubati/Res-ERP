<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers;

use App\Models\AdvanceWage;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\FinalApprovalHandler;
use Illuminate\Database\Eloquent\Model;

class AdvanceWageFinalApprovalHandler implements FinalApprovalHandler
{
    public function handle(Model&ApprovableRecord $record, User $approvedBy): void
    {
        if (! $record instanceof AdvanceWage) {
            return;
        }

        $record->update([
            'approved_by' => $approvedBy->id,
            'approved_at' => now(),
        ]);
    }
}
