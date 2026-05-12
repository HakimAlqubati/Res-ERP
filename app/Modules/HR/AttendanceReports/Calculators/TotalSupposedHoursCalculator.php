<?php

namespace App\Modules\HR\AttendanceReports\Calculators;

use App\Models\Employee;
use App\Models\EmployeePeriodHistory;
use App\Models\EmployeeServiceTermination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class TotalSupposedHoursCalculator
 * 
 * A specialized, high-performance calculator dedicated exclusively to computing 
 * the total supposed work hours (totalSupposed) for an employee within a given date range.
 * It bypasses heavy report generation and returns only the final computed value.
 */
class TotalSupposedHoursCalculator
{
    /**
     * Internal calculation logic returning raw stats.
     */
    protected function getCalculationStats(Employee $employee, Carbon|string $startDate, Carbon|string $endDate): array
    {
        $start = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        $end   = $endDate instanceof Carbon ? $endDate->copy() : Carbon::parse($endDate);
        
        $startDateStr = $start->toDateString();
        $endDateStr   = $end->toDateString();

        $histories = EmployeePeriodHistory::with('workPeriod')
            ->active()
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $endDateStr)
            ->where(function ($query) use ($startDateStr) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDateStr);
            })
            ->get();

        $leaves = DB::table('hr_employee_applications')
            ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
            ->where('hr_employee_applications.application_type_id', 1)
            ->where('hr_employee_applications.status', 'approved')
            ->where('hr_employee_applications.employee_id', $employee->id)
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->where('hr_leave_requests.start_date', '<=', $endDateStr)
                  ->where('hr_leave_requests.end_date', '>=', $startDateStr);
            })
            ->select('hr_leave_requests.start_date as from_date', 'hr_leave_requests.end_date as to_date')
            ->get();

        $termination = DB::table('hr_employee_service_terminations')
            ->where('employee_id', $employee->id)
            ->where('status', EmployeeServiceTermination::STATUS_APPROVED)
            ->join('hr_employees', 'hr_employees.id', '=', 'hr_employee_service_terminations.employee_id')
            ->where('hr_employees.active', 0)
            ->select('termination_date')
            ->first();

        $termDate = $termination ? $termination->termination_date : null;

        $totalSecondsAllDays = 0;
        $deductionSeconds = 0;
        $workedDaysCount = 0;
        $deductionDaysCount = 0;

        $date = $start->copy();
        $dayCounter = 0;

        while ($date->lte($end)) {
            $currentDay = strtolower($date->format('D'));
            $dateString = $date->toDateString();
            $dayCounter++; 

            if ($termDate && Carbon::parse($termDate)->lt($date)) {
                $date->addDay();
                continue;
            }

            $onLeave = $leaves->first(function ($l) use ($date) {
                return $date->between($l->from_date, $l->to_date);
            });

            if ($onLeave) {
                $date->addDay();
                continue;
            }

            $matchingPeriods = $histories->filter(function ($history) use ($dateString, $currentDay) {
                $dayMatch = $this->getDayOfWeekValue($history->day_of_week) === $currentDay;
                $startOk  = is_null($history->start_date) || $history->start_date <= $dateString;
                $endOk    = is_null($history->end_date) || $history->end_date >= $dateString;
                return $dayMatch && $startOk && $endOk;
            });

            $dailySeconds = 0;
            foreach ($matchingPeriods as $history) {
                $startRaw = $history->start_time ?? $history?->workPeriod?->start_at;
                $endRaw   = $history->end_time ?? $history?->workPeriod?->end_at;
                
                if (!$startRaw || !$endRaw) continue;

                try {
                    $startCarbon = Carbon::createFromFormat('H:i:s', Carbon::parse($startRaw)->format('H:i:s'));
                    $endCarbon   = Carbon::createFromFormat('H:i:s', Carbon::parse($endRaw)->format('H:i:s'));

                    if ($history?->workPeriod?->day_and_night == 1 || $endCarbon->lte($startCarbon)) {
                        $endCarbon->addDay();
                    }

                    $dailySeconds += $startCarbon->diffInSeconds($endCarbon);
                } catch (\Exception $e) {
                    continue; 
                }
            }

            if ($dailySeconds > 0) {
                $totalSecondsAllDays += $dailySeconds;
                $workedDaysCount++;
            }

            if ($dayCounter % 7 === 0) {
                $deductionSeconds += $dailySeconds;
                if ($dailySeconds > 0) {
                    $deductionDaysCount++;
                }
            }

            $date->addDay();
        }

        $earliestHistoryStart = $histories->min('start_date');
        $employeeStartedFromBeginning = $earliestHistoryStart !== null 
            && Carbon::parse($earliestHistoryStart)->lte($start);

        $isFullMonth = $start->day === 1 
            && $end->day === $end->daysInMonth 
            && $start->month === $end->month 
            && $start->year === $end->year 
            && $employeeStartedFromBeginning;

        $adjustedSeconds = $totalSecondsAllDays;
        $adjustedDays = $workedDaysCount;
        
        if ($isFullMonth && $dayCounter > 4 && $employee->has_auto_weekly_leave) {
            $adjustedSeconds = max(0, $totalSecondsAllDays - $deductionSeconds);
            $adjustedDays = max(0, $workedDaysCount - $deductionDaysCount);
        }

        return [
            'adjusted_seconds' => $adjustedSeconds,
            'adjusted_days'    => $adjustedDays,
        ];
    }

    /**
     * Calculate the total supposed hours efficiently.
     *
     * @param Employee $employee The target employee.
     * @param Carbon|string $startDate The start date.
     * @param Carbon|string $endDate The end date.
     * @param bool $returnFormatted If true, returns formatted string like "180 h 30 m". If false, returns total float hours.
     * @return string|float
     */
    public function calculate(Employee $employee, Carbon|string $startDate, Carbon|string $endDate, bool $returnFormatted = true): string|float
    {
        $stats = $this->getCalculationStats($employee, $startDate, $endDate);
        
        $totalHoursFloat = $stats['adjusted_seconds'] / 3600;

        if ($returnFormatted) {
            $hours = floor($totalHoursFloat);
            $minutes = round(($totalHoursFloat - $hours) * 60);
            return "{$hours} h {$minutes} m";
        }

        return round($totalHoursFloat, 2);
    }

    /**
     * Calculate the average daily supposed hours (rounded to nearest whole number).
     * Formula: Total Supposed Hours / Net Working Days
     * 
     * @return int Average daily hours
     */
    public function calculateAverageDailyHours(Employee $employee, Carbon|string $startDate, Carbon|string $endDate): int
    {
        $stats = $this->getCalculationStats($employee, $startDate, $endDate);
        
        if ($stats['adjusted_days'] == 0) {
            return 0;
        }

        $totalHoursFloat = $stats['adjusted_seconds'] / 3600;
        return (int) round($totalHoursFloat / $stats['adjusted_days']);
    }

    /**
     * Extract the day value from history safely.
     */
    protected function getDayOfWeekValue($day)
    {
        return is_object($day) && property_exists($day, 'value') ? $day->value : $day;
    }
}
