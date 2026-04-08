<?php

namespace App\Modules\HR\AttendanceReports\Services;

use App\Models\Employee;
use App\Modules\HR\AttendanceReports\Data\AttendanceDataFetcher;
use App\Modules\HR\AttendanceReports\Processors\AttendanceDayProcessor;
use App\Modules\HR\AttendanceReports\Processors\AttendanceStatisticsInjector;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class EmployeesAttendanceOnDateService
 * 
 * An orchestrator service class utilized for generating an organizational attendance report
 * targeting multiple employees on a single given date. By leveraging the DataFetcher, DayProcessor,
 * and StatisticsInjector, it guarantees optimal performance (O(1) database queries) regardless
 * of the number of users requested.
 */
class EmployeesAttendanceOnDateService
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
     * Orchestrate the extraction and calculation phase for the grouped attendance report.
     * 
     * @param \Illuminate\Support\Collection|array|int $employeeIdsOrEmployees The requested employees.
     * @param string $date The target date mapped as 'Y-m-d'.
     * @return Collection A collection mapped by Employee ID containing the deeply formatted UI reports.
     */
    public function fetchAttendances($employeeIdsOrEmployees, $date): Collection
    {
        $employeeIds = $employeeIdsOrEmployees instanceof Collection
            ? $employeeIdsOrEmployees->pluck('id')->toArray()
            : (is_array($employeeIdsOrEmployees) ? $employeeIdsOrEmployees : [$employeeIdsOrEmployees]);

        $dateCarbon = Carbon::parse($date);
        $dateStr = $dateCarbon->toDateString();
        $dayName = $dateCarbon->translatedFormat('l');
        $dayShort = strtolower($dateCarbon->format('D'));
        $isFuture = $dateCarbon->gt(Carbon::today());
        $isToday = $dateCarbon->isToday();

        $employeesMap = Employee::whereIn('id', $employeeIds)->get(['id', 'name', 'discount_exception_if_attendance_late', 'has_auto_weekly_leave'])->keyBy('id');

        $data = $this->fetcher->fetchForMultiEmployeesSingleDate($employeeIds, $dateStr);
        extract($data);

        $results = collect();

        foreach ($employeeIds as $empId) {
            $employee = $employeesMap->get($empId);
            if (!$employee) continue;

            $this->statsInjector->reset();
            $report = collect();

            $empTerminationDate = $terminations->get($empId);
            if ($empTerminationDate && Carbon::parse($empTerminationDate)->lt($dateCarbon)) {
                $report->put($dateStr, $this->processor->buildTerminatedDay($dateStr, $dayName));
            } else {
                $leave = $leaves->get($empId);
                if ($leave) {
                    $report->put($dateStr, $this->processor->buildLeaveDay($dateStr, $dayName, $leave));
                } else {
                    $dayHistories = $histories->where('employee_id', $empId)->filter(function ($h) use ($dayShort, $dateStr) {
                        $dayVal = is_object($h->day_of_week) && property_exists($h->day_of_week, 'value') ? $h->day_of_week->value : $h->day_of_week;
                        return $dayVal === $dayShort;
                    });

                    $dayAttendances = ($attendances->get($empId) ?? collect())->groupBy('period_id');
                    $dayOvertimes = ($overtimes->get($empId) ?? collect());

                    $dayReport = $this->processor->processDay(
                        $dateStr,
                        $dayName,
                        $dayShort,
                        $dayHistories,
                        $dayAttendances,
                        $dayOvertimes,
                        $workPeriodMap,
                        $isFuture,
                        $isToday,
                        $employee->discount_exception_if_attendance_late,
                        $this->statsInjector
                    );
                    $report->put($dateStr, $dayReport);
                }
            }

            $this->statsInjector->inject($report, $employee);

            $results->put($empId, [
                'employee' => ['id' => $empId, 'name' => $employee->name],
                'attendance_report' => $report,
            ]);
        }

        return $results;
    }
}
