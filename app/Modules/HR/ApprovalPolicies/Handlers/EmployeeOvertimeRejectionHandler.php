<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers;

use App\Models\EmployeeOvertime;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\RejectionHandler;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeRejectionHandler implements RejectionHandler
{
    public function handle(Model&ApprovableRecord $record, User $rejectedBy, ?string $reason = null): void
    {
        if (! $record instanceof EmployeeOvertime) {
            return;
        }

        $record->update([
            'status' => EmployeeOvertime::STATUS_REJECTED,
            'rejected_by' => $rejectedBy->id,
            'rejected_at' => now(),
            'notes' => $reason,
        ]);
    }
}
