<?php

namespace App\Services\HR\Validation;

use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Exceptions\HR\EmployeePeriodCoverageException;
use Carbon\Carbon;
use Setting;

class EmployeePeriodCoverageValidator
{
    public function __construct(
        protected EmployeePeriodHistoryService $periodHistoryService
    ) {}

    /**
     * يرمي Exception إذا لم يتم تغطية عدد الأيام المطلوبة
     */
    public function handle(Employee $employee, int $year, int $month): void
    {
        $requiredDays = $employee->working_days ?? (int) setting('default_employee_working_days', 26);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();

        $daysWithPeriods = $this->periodHistoryService
            ->getEmployeePeriodsByDateRange($employee, $start, $end);

        $actualDays = count($daysWithPeriods);

        if ($actualDays < $requiredDays) {
            throw new EmployeePeriodCoverageException(
                requiredDays: $requiredDays,
                actualDays: $actualDays,
                missingDays: $requiredDays - $actualDays,
                employeeName: $employee->name,
                employeeNo: $employee->employee_no,
            );
        }
    }
}
