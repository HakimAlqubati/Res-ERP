<?php

namespace App\Modules\HR\AttendanceReports;

use App\Models\Employee;
use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;
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

    public function __construct(
        EmployeeAttendanceRangeService $rangeService,
        EmployeesAttendanceOnDateService $dateService
    ) {
        $this->rangeService = $rangeService;
        $this->dateService = $dateService;
    }

    public function getEmployeeRangeReport(Employee $employee, Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->rangeService->fetchRange($employee, $startDate, $endDate);
    }

    public function getEmployeesDateReport($employeeIdsOrEmployees, $date): Collection
    {
        return $this->dateService->fetchAttendances($employeeIdsOrEmployees, $date);
    }
}
