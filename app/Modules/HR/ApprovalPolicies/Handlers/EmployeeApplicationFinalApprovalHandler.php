<?php

namespace App\Modules\HR\ApprovalPolicies\Handlers;

use App\Models\EmployeeApplicationV2;
use App\Models\User;
use App\Modules\HR\ApprovalPolicies\Contracts\ApprovableRecord;
use App\Modules\HR\ApprovalPolicies\Contracts\FinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Exceptions\ApprovalWorkflowException;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplications\AdvanceRequestFinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplications\LeaveRequestFinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplications\MealRequestFinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplications\MissedCheckInFinalApprovalHandler;
use App\Modules\HR\ApprovalPolicies\Handlers\EmployeeApplications\MissedCheckOutFinalApprovalHandler;
use Illuminate\Database\Eloquent\Model;

class EmployeeApplicationFinalApprovalHandler implements FinalApprovalHandler
{
    /**
     * @var array<int, class-string<FinalApprovalHandler>>
     */
    private array $handlers = [
        EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST => LeaveRequestFinalApprovalHandler::class,
        EmployeeApplicationV2::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => MissedCheckInFinalApprovalHandler::class,
        EmployeeApplicationV2::APPLICATION_TYPE_ADVANCE_REQUEST => AdvanceRequestFinalApprovalHandler::class,
        EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => MissedCheckOutFinalApprovalHandler::class,
        EmployeeApplicationV2::APPLICATION_TYPE_MEAL_REQUEST => MealRequestFinalApprovalHandler::class,
    ];

    public function handle(Model&ApprovableRecord $record, User $approvedBy): void
    {
        if (! $record instanceof EmployeeApplicationV2) {
            return;
        }

        $handlerClass = $this->handlers[(int) $record->application_type_id] ?? null;

        if (! $handlerClass) {
            throw new ApprovalWorkflowException("No final approval handler found for employee application type {$record->application_type_id}.");
        }

        app($handlerClass)->handle($record, $approvedBy);
    }
}
