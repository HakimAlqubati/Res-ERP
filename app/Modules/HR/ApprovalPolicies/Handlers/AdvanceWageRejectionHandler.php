<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers;

use App\Models\AdvanceWage;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\RejectionHandler;
use Illuminate\Database\Eloquent\Model;

class AdvanceWageRejectionHandler implements RejectionHandler
{
    public function handle(Model&ApprovableRecord $record, User $rejectedBy, ?string $reason = null): void
    {
        if (! $record instanceof AdvanceWage) {
            return;
        }

        $record->update([
            'status' => AdvanceWage::STATUS_CANCELLED,
            'notes' => $reason,
        ]);
    }
}
