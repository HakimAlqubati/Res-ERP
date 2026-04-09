<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;

class EmployeesAttendanceOnDateService
{
    protected AttendanceReportInterface $reportManager;

    public function __construct(AttendanceReportInterface $reportManager)
    {
        $this->reportManager = $reportManager;
    }

    /**
     * جلب تقرير حضور عدة موظفين في يوم واحد فقط.
     *
     * @param array|Collection $employeeIdsOrEmployees معرفات الموظفين أو مجموعة Employees
     * @param Carbon|string $date التاريخ المطلوب (كـ Carbon أو 'Y-m-d')
     * @return Collection [employee_id => attendance_report]
     */
    public function fetchAttendances($employeeIdsOrEmployees, $date): Collection
    {
        $date    = $date instanceof Carbon ? $date : Carbon::parse($date);
        $results = collect();

        if ($employeeIdsOrEmployees instanceof Collection && $employeeIdsOrEmployees->first() instanceof Employee) {
            $employees = $employeeIdsOrEmployees;
            $chunkReports = $this->reportManager->getEmployeesRangeReport($employees, $date, $date);
            foreach ($employees as $employee) {
                $report = $chunkReports->get($employee->id) ?? collect();
                $results->put($employee->id, [
                    'employee'          => [
                        'id'   => $employee->id,
                        'name' => $employee->name,
                    ],
                    'attendance_report' => $report->except('statistics'),
                ]);
            }
        } else {
            // إذا تم تمرير معرفات الموظفين (IDs)
            $employeeIds = is_array($employeeIdsOrEmployees)
                ? $employeeIdsOrEmployees
                : collect($employeeIdsOrEmployees)->toArray();

            // استخدم chunk لتقسيم الموظفين إلى دفعات (مثلاً 100 لكل دفعة)
            Employee::whereIn('id', $employeeIds)
                ->chunk(100, function ($chunkedEmployees) use ($date, &$results) {
                    $chunkReports = $this->reportManager->getEmployeesRangeReport($chunkedEmployees, $date, $date);
                    foreach ($chunkedEmployees as $employee) {
                        $report = $chunkReports->get($employee->id) ?? collect();
                        $results->put($employee->id, [
                            'employee'          => [
                                'id'   => $employee->id,
                                'name' => $employee->name,
                            ],
                            'attendance_report' => $report->except('statistics'),
                        ]);
                    }
                });
        }

        return $results;
    }
}
