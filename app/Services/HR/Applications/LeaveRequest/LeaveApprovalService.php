<?php

namespace App\Services\HR\Applications\LeaveRequest;

use App\Models\EmployeeApplicationV2;
use App\Models\LeaveBalance;
use App\Exceptions\HR\LeaveApprovalException;
use Exception;

class LeaveApprovalService
{
    /**
     * Process the approval side-effects of a Leave Request.
     * Deducts the approved leave duration from the employee's leave balance.
     *
     * @param EmployeeApplicationV2 $record
     * @return void
     * @throws Exception
     */
    public function process(EmployeeApplicationV2 $record): void
    {
        if (!$record->leaveRequest) {
            throw LeaveApprovalException::missingDetails();
        }

        $leaveBalance = LeaveBalance::getLeaveBalanceForEmployee(
            $record->employee_id,
            $record->leaveRequest->year,
            $record->leaveRequest->leave_type,
            $record->leaveRequest->month
        );

        if ($leaveBalance) {
            $leaveBalance->decrement('balance', $record->leaveRequest->days_count);
        } else {
            throw LeaveApprovalException::balanceNotFound(
                $record->leaveRequest->year,
                $record->leaveRequest->month,
                $record->leaveRequest->leave_type
            );
        }
    }

    /**
     * Revert the approval side-effects of a Leave Request.
     * Increments the leave duration back to the employee's leave balance.
     *
     * @param EmployeeApplicationV2 $record
     * @return void
     * @throws Exception
     */
    public function undoProcess(EmployeeApplicationV2 $record): void
    {
        if (!$record->leaveRequest) {
            throw LeaveApprovalException::missingDetails();
        }

        $leaveBalance = LeaveBalance::getLeaveBalanceForEmployee(
            $record->employee_id,
            $record->leaveRequest->year,
            $record->leaveRequest->leave_type,
            $record->leaveRequest->month
        );

        if ($leaveBalance) {
            $leaveBalance->increment('balance', $record->leaveRequest->days_count);
        } else {
            throw LeaveApprovalException::balanceNotFound(
                $record->leaveRequest->year, 
                $record->leaveRequest->month, 
                $record->leaveRequest->leave_type
            );
        }
    }
}
