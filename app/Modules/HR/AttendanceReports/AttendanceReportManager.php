<?php

namespace App\Modules\HR\AttendanceReports;

use App\Models\Employee;
use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;
use App\Modules\HR\AttendanceReports\Data\AttendanceDataFetcher;
use App\Modules\HR\AttendanceReports\Services\EmployeeAttendanceRangeService;
use App\Modules\HR\AttendanceReports\Services\EmployeesAttendanceOnDateService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class AttendanceReportManager
 * 
 * A unified entry-point (Facade/Manager pattern) implementing the abstract contract
 * to orchestrate and delegate report requests dynamically.
 */
class AttendanceReportManager implements AttendanceReportInterface
{
    private EmployeeAttendanceRangeService $rangeService;
    private EmployeesAttendanceOnDateService $dateService;
    private AttendanceDataFetcher $fetcher;

    public function __construct(
        EmployeeAttendanceRangeService $rangeService,
        EmployeesAttendanceOnDateService $dateService,
        AttendanceDataFetcher $fetcher
    ) {
        $this->rangeService = $rangeService;
        $this->dateService = $dateService;
        $this->fetcher = $fetcher;
    }

    public function getEmployeeRangeReport(Employee $employee, Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->rangeService->fetchRange($employee, $startDate, $endDate);
    }

    public function getEmployeesDateReport($employeeIdsOrEmployees, $date): Collection
    {
        return $this->dateService->fetchAttendances($employeeIdsOrEmployees, $date);
    }
    public function getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date): Collection
    {
        return $this->fetcher->getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
    }
    public function getEmployeesRangeReport($employees, Carbon $startDate, Carbon $endDate): Collection
    {
        $employees = collect($employees);
        $empIds = $employees->pluck('id')->toArray();
        $bulkData = $this->fetcher->fetchForMultiEmployeesRange($empIds, $startDate->toDateString(), $endDate->toDateString());

        $results = collect();
        foreach ($employees as $employee) {
            $employeeData = [
                'histories'     => ($bulkData['histories'][$employee->id] ?? collect()),
                'attendances'   => ($bulkData['attendances'][$employee->id] ?? collect()),
                'leaves'        => ($bulkData['leaves'][$employee->id] ?? collect()),
                'terminations'  => ($bulkData['terminations'][$employee->id] ?? null),
                'overtimes'     => ($bulkData['overtimes'][$employee->id] ?? collect()),
                'workPeriodMap' => $bulkData['workPeriodMap'],
            ];

            $results->put($employee->id, $this->rangeService->processRangeWithData($employee, $startDate, $endDate, $employeeData));
        }

        return $results;
    }
}
