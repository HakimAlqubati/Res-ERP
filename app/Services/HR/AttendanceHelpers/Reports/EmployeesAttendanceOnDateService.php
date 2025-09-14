<?php
namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeesAttendanceOnDateService
{
    protected AttendanceFetcher $attendanceFetcher;

    public function __construct(AttendanceFetcher $attendanceFetcher)
    {
        $this->attendanceFetcher = $attendanceFetcher;
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
            // إذا تم تمرير مجموعة Employees بالفعل
            $employees = $employeeIdsOrEmployees;
            foreach ($employees as $employee) {
                $report = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $date, $date);
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
                    foreach ($chunkedEmployees as $employee) {
                        $report = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $date, $date);
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