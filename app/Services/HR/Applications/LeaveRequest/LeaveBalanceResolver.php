<?php

namespace App\Services\HR\Applications\LeaveRequest;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class LeaveBalanceResolver
 *
 * Single-responsibility helper: locates the correct LeaveBalance record
 * for a given leave request, abstracting away the year-based lookup logic.
 *
 * Rules:
 *  - Match is always by (employee_id, leave_type_id, year).
 *  - Month is intentionally ignored — consumption is tracked annually.
 *
 * @package App\Services\HR\Applications\LeaveRequest
 */
class LeaveBalanceResolver
{
    /**
     * Find the leave balance record that corresponds to the given leave request.
     * Returns null if no matching balance record exists.
     *
     * @param  LeaveRequest $leaveRequest
     * @return LeaveBalance|null
     */
    public function resolve(LeaveRequest $leaveRequest): ?LeaveBalance
    {
        $leaveTypeId = (int) $leaveRequest->leave_type; // stored as string in DB
        $year        = (int) $leaveRequest->year;

        // dd($year,$leaveTypeId,$leaveRequest);
        $balance = LeaveBalance::query()
            ->where('employee_id',   $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year',          $year)
            ->first();

        if (! $balance) {
            Log::warning('[LeaveBalanceResolver] No balance record found.', [
                'employee_id'   => $leaveRequest->employee_id,
                'leave_type_id' => $leaveTypeId,
                'year'          => $year,
            ]);
        }

        return $balance;
    }
}
