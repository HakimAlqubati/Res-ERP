<?php

namespace App\Services\HR\PayrollHelpers;

use App\Models\LeaveBalance;
use Carbon\Carbon;

class LeaveBalanceService
{
    public function getLeaveMonthlyBalance($employee, $yearAndMonth)
    {
        $year = Carbon::parse($yearAndMonth)->year; // Extracts the year
        $month = Carbon::parse($yearAndMonth)->month; // Extracts the month
        $monthlyBalance = LeaveBalance::getMonthlyBalanceForEmployee($employee->id, $year, $month)?->balance ?? 0;
        return $monthlyBalance;
    }
}