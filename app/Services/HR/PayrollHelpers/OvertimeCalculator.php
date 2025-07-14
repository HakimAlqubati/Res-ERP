<?php
namespace App\Services\HR\PayrollHelpers;

use App\Models\LeaveBalance;
use Carbon\Carbon;

class OvertimeCalculator
{
    public function getEmployeeOvertimes($date, $employee)
    {
        $month = \Carbon\Carbon::parse($date)->month; // Get the month from the given date

        // Filter the overtimes to only include those that match the same month
        $overtimesForMonth = $employee->overtimesofMonth($date)
            ->get() // Retrieve the collection first
            ->filter(function ($overtime) use ($month) {
                return \Carbon\Carbon::parse($overtime->date)->month == $month;
            });

        $totalHours = $overtimesForMonth->sum(function ($overtime) {
            return (float) $overtime->hours; // Ensure the 'hours' value is cast to float
        });
        return $totalHours;
    }

    public function getEmployeeOvertimesOfSpecificDate($date, $employee)
    {
        $month = \Carbon\Carbon::parse($date)->month; // Get the month from the given date

        // Filter the overtimes to only include those that match the same month
        $overtimesForMonth = $employee->overtimesByDate($date)
            ->get() // Retrieve the collection first
            ->filter(function ($overtime) use ($month) {
                return \Carbon\Carbon::parse($overtime->date)->month == $month;
            });

        $totalHours = $overtimesForMonth->sum(function ($overtime) {
            return (float) $overtime->hours; // Ensure the 'hours' value is cast to float
        });

        return $totalHours;
    }

    public function getEmployeeOvertimesV2($date, $employee)
    {
        // Alternative overtime calculation
        return [0, $date];
    }

    public function createEmployeeOverime($employee, $date)
    {
        $year = Carbon::parse($date)->year; // Extracts the year
        $month = Carbon::parse($date)->month; // Extracts the month
        $monthlyBalance = LeaveBalance::getMonthlyBalanceForEmployee($employee->id, $year, $month)?->balance ?? 0;
        $totalDayHours = $employee?->working_hours ?? 0;
        return ($monthlyBalance * $totalDayHours);
    }
}