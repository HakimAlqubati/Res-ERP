<?php

namespace App\Modules\HR\AttendanceReports\Services;

use App\Models\Employee;
use App\Modules\HR\AttendanceReports\Data\AttendanceDataFetcher;
use App\Modules\HR\AttendanceReports\Processors\AttendanceDayProcessor;
use App\Modules\HR\AttendanceReports\Processors\AttendanceStatisticsInjector;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class EmployeeAttendanceRangeService
 * 
 * An orchestrator service class responsible for generating comprehensive attendance reports 
 * spanning a wide date range for a single selected employee. It maximizes performance by computing
 * daily iterations in-memory against pre-fetched grouped datasets.
 */
class EmployeeAttendanceRangeService
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

    /**
     * Orchestrate the extraction and processing logic over the specified date range.
     * 
     * Applies iterative looping securely, injecting individual daily structures and global 
     * range statistics matching the UI requirements seamlessly.
     * 
     * @param Employee $employee The singular targeted employee model.
     * @param Carbon $startDate The bounds mapping the beginning of the evaluation.
     * @param Carbon $endDate The limits mapping the ending of the evaluation bounds.
     * @return Collection The sequential collection of evaluated day reports and inclusive metadata.
     */
    public function fetchRange(Employee $employee, Carbon $startDate, Carbon $endDate): Collection
    {
        $startDateStr = $startDate->toDateString();
        $endDateStr   = $endDate->toDateString();
        $empId        = $employee->id;

        $data = $this->fetcher->fetchForSingleEmployeeRange($empId, $startDateStr, $endDateStr);

        return $this->processRangeWithData($employee, $startDate, $endDate, $data);
    }

    /**
     * Process the attendance report using a pre-fetched dataset.
     * This is the CORE logic of the range report, extracted for bulk reuse.
     * 
     * @param Employee $employee Target employee model.
     * @param Carbon $startDate Start bounds.
     * @param Carbon $endDate End bounds.
     * @param array $data Pre-fetched collections (histories, attendances, etc.).
     * @return Collection Processed report.
     */
    public function processRangeWithData(Employee $employee, Carbon $startDate, Carbon $endDate, array $data): Collection
    {
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

            // Terminated logic
            $termDate = is_array($terminations) ? ($terminations[$employee->id] ?? null) : ($terminations->termination_date ?? null);
            if ($termDate && Carbon::parse($termDate)->lt($tempDate)) {
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

            $currentDateStrFixed = $currentDateStr;
            $dayHistories = $histories->filter(function ($h) use ($currentDayShort, $currentDateStrFixed) {
                $dayVal = is_object($h->day_of_week) && property_exists($h->day_of_week, 'value') ? $h->day_of_week->value : $h->day_of_week;
                return $dayVal === $currentDayShort && $h->start_date <= $currentDateStrFixed && (is_null($h->end_date) || $h->end_date >= $currentDateStrFixed);
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
                $employee->discount_exception_if_attendance_late,
                $this->statsInjector
            );

            $report->put($currentDateStr, $dayReport);
            $tempDate->addDay();
        }

        $isPreviousMonth = $startDate->format('Y-m') < now()->format('Y-m');
        if ($isPreviousMonth && $employee->has_auto_weekly_leave) {
            $fetcher = new AttendanceFetcher(new \App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService());
            $fetcher->applyWeeklyLeaveToAbsences($report, $isPreviousMonth);
        }

        $isFullMonth = $startDate->day === 1
            && $endDate->day === $endDate->daysInMonth
            && $startDate->month === $endDate->month
            && $startDate->year === $endDate->year;

        $earliestHistoryStart = $histories->min('start_date');
        $employeeStartedFromBeginning = $earliestHistoryStart !== null
            && Carbon::parse($earliestHistoryStart)->lte($startDate);

        if ($isFullMonth && $employeeStartedFromBeginning && $report->count() > 4) {
            $deductionSeconds = 0;
            $chunks = $report->values()->chunk(7);

            foreach ($chunks as $week) {
                if ($week->count() < 7) continue;
                $lastDay = $week->last();
                if (isset($lastDay['daily_supposed_seconds'])) {
                    $deductionSeconds += $lastDay['daily_supposed_seconds'];
                }
            }

            $this->statsInjector->subtractTotalDurationSeconds($deductionSeconds);
        }

        $this->statsInjector->inject($report, $employee);

        return $report;
    }
}
