<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers;

use App\Models\EmployeeApplicationV2;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\RejectionHandler;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowGuard;
use App\Services\HR\Applications\EmployeeApplicationService;
use Illuminate\Database\Eloquent\Model;

class EmployeeApplicationRejectionHandler implements RejectionHandler
{
    public function __construct(
        private readonly EmployeeApplicationService $applicationService,
        private readonly ApprovalWorkflowGuard $guard,
    ) {
    }

    public function handle(Model&ApprovableRecord $record, User $rejectedBy, ?string $reason = null): void
    {
        if (! $record instanceof EmployeeApplicationV2) {
            return;
        }

        $this->guard->withoutGuard(
            fn () => $this->applicationService->rejectApplication($record->id, $rejectedBy->id, $reason ?? '')
        );
    }
}
