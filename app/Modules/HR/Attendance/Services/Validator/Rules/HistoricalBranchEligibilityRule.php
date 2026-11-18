<?php

declare(strict_types=1);

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Modules\HR\Attendance\Enums\AttendanceType;
use Carbon\Carbon;

/**
 * Rule class validating whether an attendance payload's branch_id is permitted
 * for the employee on the given date.
 *
 * Preserves live attendance restrictions while allowing approved historical
 * missed check-in / check-out requests for branches the employee belonged to
 * at that date according to hr_employee_branch_logs.
 */
final class HistoricalBranchEligibilityRule
{
    public function isAllowed(
        Employee $employee,
        ?int $branchId,
        Carbon $requestTime,
        ?string $attendanceType = null
    ): bool {
        if ($branchId === null) {
            return true;
        }

        if ($branchId === (int) $employee->branch_id || (bool) $employee->allow_attendance_from_any_branch) {
            return true;
        }

        if ($attendanceType === AttendanceType::REQUEST->value) {
            return EmployeeBranchLog::employeeBelongsToBranchAtDate(
                employeeId: (int) $employee->id,
                branchId: $branchId,
                date: $requestTime
            );
        }

        return false;
    }
}
