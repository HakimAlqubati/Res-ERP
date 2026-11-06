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
    public function getEmployeesRangeReport($employees, Carbon $startDate, Carbon $endDate, bool $excludeNoShift = false): Collection
    {
        $employees = collect($employees);
        $results = collect();
        
        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();
        
        // Optimize: Cache at the employee level if fetching a single day
        $isSingleDay = $startDateStr === $endDateStr;
        $uncachedEmployees = collect();
        
        $tenantDb = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();

        foreach ($employees as $employee) {
            if ($isSingleDay) {
                $cacheKey = "emp_daily_attendance_report_{$tenantDb}_{$employee->id}_{$startDateStr}";
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    $results->put($employee->id, \Illuminate\Support\Facades\Cache::get($cacheKey));
                    continue;
                }
            }
            $uncachedEmployees->push($employee);
        }

        if ($uncachedEmployees->isNotEmpty()) {
            $empIds = $uncachedEmployees->pluck('id')->toArray();
            $bulkData = $this->fetcher->fetchForMultiEmployeesRange($empIds, $startDateStr, $endDateStr, $excludeNoShift);

            foreach ($uncachedEmployees as $employee) {
                $termination = $bulkData['terminations'][$employee->id] ?? null;
                if ($termination && $employee->join_date && \Carbon\Carbon::parse($termination->termination_date)->lte(\Carbon\Carbon::parse($employee->join_date))) {
                    $termination = null;
                }

                $employeeData = [
                    'histories'     => ($bulkData['histories'][$employee->id] ?? collect()),
                    'attendances'   => ($bulkData['attendances'][$employee->id] ?? collect()),
                    'leaves'        => ($bulkData['leaves'][$employee->id] ?? collect()),
                    'terminations'  => $termination,
                    'overtimes'     => ($bulkData['overtimes'][$employee->id] ?? collect()),
                    'workPeriodMap' => $bulkData['workPeriodMap'],
                ];

                $processedData = $this->rangeService->processRangeWithData($employee, $startDate, $endDate, $employeeData);
                $results->put($employee->id, $processedData);

                if ($isSingleDay) {
                    $cacheKey = "emp_daily_attendance_report_{$tenantDb}_{$employee->id}_{$startDateStr}";
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $processedData, now()->addHours(4));
                }
            }
        }

        return $results;
    }
}