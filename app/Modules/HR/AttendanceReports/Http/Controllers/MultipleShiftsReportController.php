<?php

namespace App\Modules\HR\AttendanceReports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeePeriodHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MultipleShiftsReportController extends Controller
{
    /**
     * Generate the temporary report for employees with multiple shifts in a single day.
     */
    public function index(Request $request)
    {
        // Default to the current month if start_date or end_date are not provided
        $startDateStr = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateStr = $request->input('end_date', now()->endOfMonth()->toDateString());

        $startDate = Carbon::parse($startDateStr);
        $endDate = Carbon::parse($endDateStr);

        // Fetch all active employee period histories overlapping with the date range
        $histories = EmployeePeriodHistory::with(['employee', 'workPeriod', 'branch'])
            ->where('active', 1)
            ->where('start_date', '<=', $endDateStr)
            ->where(function ($q) use ($startDateStr) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $startDateStr);
            })
            ->get();

        $reportData = [];
        $tempDate = $startDate->copy();

        while ($tempDate->lte($endDate)) {
            $currentDateStr = $tempDate->toDateString();
            $currentDayShort = strtolower($tempDate->format('D'));

            // Filter histories active on this specific day and matching the day of week
            $dayHistories = $histories->filter(function ($h) use ($currentDayShort, $currentDateStr) {
                $dayVal = is_object($h->day_of_week) && property_exists($h->day_of_week, 'value') ? $h->day_of_week->value : $h->day_of_week;
                return $dayVal === $currentDayShort 
                    && $h->start_date <= $currentDateStr 
                    && (is_null($h->end_date) || $h->end_date >= $currentDateStr);
            });

            // Group by employee
            $groupedByEmployee = $dayHistories->groupBy('employee_id');

            foreach ($groupedByEmployee as $employeeId => $empHistories) {
                // If the employee has more than 1 unique shift (period) assigned for this day
                $uniquePeriods = $empHistories->unique('period_id');
                if ($uniquePeriods->count() > 1) {
                    $employee = $empHistories->first()->employee;
                    if ($employee) {
                        $shifts = [];
                        foreach ($empHistories as $h) {
                            $wp = $h->workPeriod;
                            if ($wp) {
                                $startTime = $h->start_time ?? $wp->start_at;
                                $endTime = $h->end_time ?? $wp->end_at;
                                $shifts[] = [
                                    'name' => $wp->name,
                                    'start' => $startTime,
                                    'end' => $endTime,
                                ];
                            }
                        }

                        $branchName = $empHistories->first()->branch?->name ?? 'N/A';

                        $reportData[] = [
                            'employee_id' => $employee->id,
                            'employee_name' => $employee->name,
                            'branch_name' => $branchName,
                            'date' => $currentDateStr,
                            'shifts' => $shifts,
                        ];
                    }
                }
            }

            $tempDate->addDay();
        }

        // Return standard Blade view
        return view('hr.multiple_shifts_report', [
            'reportData' => $reportData,
            'startDate' => $startDateStr,
            'endDate' => $endDateStr,
        ]);
    }
}
