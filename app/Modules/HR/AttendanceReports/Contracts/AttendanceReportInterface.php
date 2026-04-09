<?php

namespace App\Modules\HR\AttendanceReports\Contracts;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface AttendanceReportInterface
 * 
 * Defines the unified contract for centralized attendance report retrieval.
 */
interface AttendanceReportInterface
{
    /**
     * Retrieve a detailed, bounded date-range report for a single targeted employee.
     * 
     * @param Employee $employee Target employee model.
     * @param Carbon $startDate Bounds relative to the start interval.
     * @param Carbon $endDate Bounds relative to the end interval.
     * @return Collection Formatted range attendance report.
     */
    public function getEmployeeRangeReport(Employee $employee, Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Retrieve an aggregate map of day reports for multiple employees on a static date.
     * 
     * @param \Illuminate\Support\Collection|array|int $employeeIdsOrEmployees Targeted employees dataset.
     * @param string|\Carbon\Carbon $date The explicit target date evaluated.
     * @return Collection Indexed sequential mapped employee attendance reports.
     */
    public function getEmployeesDateReport($employeeIdsOrEmployees, $date): Collection;

    /**
     * Retrieve aggregate range reports for multiple employees efficiently.
     * 
     * @param \Illuminate\Support\Collection|array $employees Targeted employees.
     * @param Carbon $startDate Start bounds.
     * @param Carbon $endDate End bounds.
     * @return Collection Indexed sequential reports (employee_id => report).
     */
    public function getEmployeesRangeReport($employees, Carbon $startDate, Carbon $endDate): Collection;

    public function getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
}
