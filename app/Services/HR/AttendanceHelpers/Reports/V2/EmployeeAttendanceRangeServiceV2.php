<?php

namespace App\Services\HR\AttendanceHelpers\Reports\V2;

use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeAttendanceRangeServiceV2
{
    private AttendanceDataFetcher $fetcher;
    private AttendanceDayProcessor $processor;
    private AttendanceStatisticsInjector $statsInjector;

    public function __construct(
        AttendanceDataFetcher $fetcher,
        AttendanceDayProcessor $processor,
        AttendanceStatisticsInjector $statsInjector
    ) {
        $this->fetcher = $fetcher;
        $this->processor = $processor;
        $this->statsInjector = $statsInjector;
    }

    public function fetchRange(Employee $employee, Carbon $startDate, Carbon $endDate): Collection
    {
        $startDateStr = $startDate->toDateString();
        $endDateStr   = $endDate->toDateString();
        $empId        = $employee->id;

        $data = $this->fetcher->fetchForSingleEmployeeRange($empId, $startDateStr, $endDateStr);
        extract($data); 

        $report = collect();
        $tempDate = $startDate->copy();

        $this->statsInjector->reset();

        while ($tempDate->lte($endDate)) {
            $currentDateStr = $tempDate->toDateString();
            $currentDayName = $tempDate->translatedFormat('l');
            $currentDayShort = strtolower($tempDate->format('D'));
            $isFuture = $tempDate->gt(Carbon::today());
            $isToday  = $tempDate->isToday();

            if ($terminations && Carbon::parse($terminations->termination_date)->lt($tempDate)) {
                $report->put($currentDateStr, $this->processor->buildTerminatedDay($currentDateStr, $currentDayName));
                $tempDate->addDay();
                continue;
            }

            $leave = $leaves->first(fn($l) => $tempDate->between($l->from_date, $l->to_date));
            if ($leave) {
                $report->put($currentDateStr, $this->processor->buildLeaveDay($currentDateStr, $currentDayName, $leave));
                $tempDate->addDay();
                continue;
            }

            $dayHistories = $histories->filter(function ($h) use ($currentDayShort, $currentDateStr) {
                $dayVal = is_object($h->day_of_week) && property_exists($h->day_of_week, 'value') ? $h->day_of_week->value : $h->day_of_week;
                return $dayVal === $currentDayShort && $h->start_date <= $currentDateStr && (is_null($h->end_date) || $h->end_date >= $currentDateStr);
            });

            $dayAttendances = ($attendances->get($currentDateStr) ?? collect())->groupBy('period_id');
            $dayOvertimes = ($overtimes->get($currentDateStr) ?? collect());

            $dayReport = $this->processor->processDay(
                $currentDateStr, 
                $currentDayName, 
                $currentDayShort, 
                $dayHistories, 
                $dayAttendances, 
                $dayOvertimes, 
                $workPeriodMap, 
                $isFuture, 
                $isToday, 
                $employee->discount_exception_if_attendance_late
            );

            $report->put($currentDateStr, $dayReport);
            $tempDate->addDay();
        }

        $isPreviousMonth = $startDate->format('Y-m') < now()->format('Y-m');
        if ($isPreviousMonth && $employee->has_auto_weekly_leave) {
            $fetcher = new AttendanceFetcher(new \App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService());
            $fetcher->applyWeeklyLeaveToAbsencesV2($report, $isPreviousMonth);
        }

        $this->statsInjector->inject($report, $employee);

        return $report;
    }
}
