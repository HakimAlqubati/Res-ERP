<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use App\Modules\HR\AttendanceReports\Contracts\AttendanceReportInterface;

class AbsentEmployeesV2Service
{
    protected AttendanceReportInterface $reportManager;

    public function __construct(AttendanceReportInterface $reportManager)
    {
        $this->reportManager = $reportManager;
    }

    /**
     * @param Carbon|string $dateFrom
     * @param Carbon|string $dateTo
     * @param array $filters (اختياري) فلاتر للموظفين مثل branch_id, department_id
     * @return Collection مجموعة تحتوي بيانات الغيابات لكل موظف
     */
    public function getAbsentEmployees($dateFrom, $dateTo, array $filters = []): Collection
    {
        $dateFrom = $dateFrom instanceof Carbon ? $dateFrom : Carbon::parse($dateFrom);
        $dateTo   = $dateTo instanceof Carbon ? $dateTo : Carbon::parse($dateTo);

        $employeesQuery = Employee::query()
            // ->where('active', 1)
        ;

        if (!empty($filters['branch_id'])) {
            $employeesQuery->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['department_id'])) {
            $employeesQuery->where('department_id', $filters['department_id']);
        }

        $employees = $employeesQuery->get();
        $results = collect();

        $chunkReportMap = collect();
        $employees->chunk(50)->each(function ($chunk) use (&$chunkReportMap, $dateFrom, $dateTo) {
            $chunkReports = $this->reportManager->getEmployeesRangeReport($chunk, $dateFrom, $dateTo);
            foreach ($chunkReports as $empId => $report) {
                $chunkReportMap->put($empId, $report);
            }
        });

        foreach ($employees as $employee) {
            $report = $chunkReportMap->get($employee->id) ?? collect();

            $absentDays = collect();

            foreach ($report as $key => $dayData) {
                // Ensure the key is a date string (Y-m-d)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)) {
                    $isAbsent = isset($dayData['day_status']) &&
                        $dayData['day_status'] === AttendanceReportStatus::Absent->value;

                    if ($isAbsent) {
                        // Optional filter for current_time on today's date
                        if (!empty($filters['current_time']) && Carbon::parse($key)->isToday()) {
                            $currentTime = $filters['current_time'];
                            $passedShiftStart = false;

                            $periods = $dayData['periods'] ?? [];

                            foreach ($periods as $period) {
                                $startTime = $period['start_time'] ?? null;
                                if (!$startTime) continue;

                                if (strtotime($currentTime) >= strtotime($startTime)) {
                                    $passedShiftStart = true;
                                    break;
                                }
                            }

                            if (!$passedShiftStart) {
                                continue;
                            }
                        }

                        $absentDays->push($dayData);
                    }
                }
            }

            if ($absentDays->isNotEmpty()) {
                $results->push([
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'branch_id' => $employee->branch_id,
                    'department_id' => $employee->department_id,
                    'absences_count' => $absentDays->count(),
                    'absences' => $absentDays->values(),
                ]);
            }
        }

        return $results->values();
    }
}
