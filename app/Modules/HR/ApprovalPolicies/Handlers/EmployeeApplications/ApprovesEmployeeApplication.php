<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplications;

use App\Models\EmployeeApplicationV2;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\FinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Services\ApprovalWorkflowGuard;
use App\Services\HR\Applications\EmployeeApplicationService;
use Illuminate\Database\Eloquent\Model;

abstract class ApprovesEmployeeApplication implements FinalApprovalHandler
{
    public function __construct(
        private readonly EmployeeApplicationService $applicationService,
        private readonly ApprovalWorkflowGuard $guard,
    ) {
    }

    public function handle(Model&ApprovableRecord $record, User $approvedBy): void
    {
        if (! $record instanceof EmployeeApplicationV2) {
            return;
        }

        $this->guard->withoutGuard(fn () => $this->applicationService->approveApplication($record->id, $approvedBy->id));
    }
}
