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

    /**
     * نسخة محسنة من جلب الحضور لعدة موظفين (Batch Processing)
     */
    public function fetchAttendancesOptimized($employeeIds, $date): Collection
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateString = $date->toDateString();
        $results = collect();

        // 1. جلب الموظفين مع العلاقات الأساسية
        $employees = Employee::whereIn('id', $employeeIds)->get();

        // 2. التحميل المسبق للبيانات لجميع الموظفين (Batch Queries)
        $allAttendances = \App\Models\Attendance::whereIn('employee_id', $employeeIds)
            ->whereDate('check_date', $dateString)
            ->accepted()
            ->get()
            ->groupBy('employee_id');

        $allLeaves = \App\Models\EmployeeApplicationV2::where('status', \App\Models\EmployeeApplicationV2::STATUS_APPROVED)
            ->join('hr_leave_requests', 'hr_employee_applications.id', '=', 'hr_leave_requests.application_id')
            ->join('hr_leave_types', 'hr_leave_requests.leave_type', '=', 'hr_leave_types.id')
            ->whereIn('hr_employee_applications.employee_id', $employeeIds)
            ->where(function ($q) use ($dateString) {
                $q->where('hr_leave_requests.start_date', '<=', $dateString)
                  ->where('hr_leave_requests.end_date', '>=', $dateString);
            })
            ->select(
                'hr_employee_applications.employee_id',
                'hr_leave_requests.start_date as from_date',
                'hr_leave_requests.end_date as to_date',
                'hr_leave_requests.leave_type',
                'hr_leave_types.name as transaction_description'
            )
            ->get()
            ->groupBy('employee_id');

        $allTerminations = \App\Models\EmployeeServiceTermination::whereIn('employee_id', $employeeIds)
            ->where('status', \App\Models\EmployeeServiceTermination::STATUS_APPROVED)
            ->get()
            ->groupBy('employee_id');

        $allOvertimes = \App\Models\EmployeeOvertime::whereIn('employee_id', $employeeIds)
            ->where('date', $dateString)
            ->where('status', \App\Models\EmployeeOvertime::STATUS_APPROVED)
            ->get()
            ->groupBy('employee_id');

        // 3. جلب فترات العمل بشكل مجمع
        $periodHistoryService = new \App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService();
        $allPeriods = $periodHistoryService->getEmployeesPeriodsByDateBatch($employeeIds, $date);

        // 4. المعالجة في الذاكرة لكل موظف
        foreach ($employees as $employee) {
            /** @var \App\Models\Employee $employee */
            $preloaded = [
                'periods'      => $allPeriods->get($employee->id),
                'leaves'       => $allLeaves->get($employee->id, collect()),
                'termination'  => $allTerminations->get($employee->id, collect())->first(),
                'attendances'  => $allAttendances->get($employee->id, collect()),
                'overtimes'    => $allOvertimes->get($employee->id, collect()),
            ];

            $report = $this->attendanceFetcher->fetchEmployeeAttendancesBatch($employee, $date, $preloaded);

            $results->put($employee->id, [
                'employee'          => [
                    'id'   => $employee->id,
                    'name' => $employee->name,
                ],
                'attendance_report' => $report,
            ]);
        }

        return $results;
    }
}
