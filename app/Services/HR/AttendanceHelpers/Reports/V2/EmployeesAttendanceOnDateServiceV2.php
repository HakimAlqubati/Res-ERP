<?php

namespace App\Services\HR\AttendanceHelpers\Reports\V2;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeesAttendanceOnDateServiceV2
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
                        $employee->discount_exception_if_attendance_late
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
