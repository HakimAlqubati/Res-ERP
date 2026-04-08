<?php

namespace App\Modules\HR\AttendanceReports\Calculators;

use App\Enums\HR\Attendance\AttendanceReportStatus;

/**
 * Class StatusResolver
 * 
 * Aggregates hierarchical nested status layers securely resolving abstract state maps.
 */
class StatusResolver
{
    /**
     * Resolve terminal attendance status state securely via aggregated arrays.
     * 
     * @param array $allPeriodsStatus Aggregated internal string values bounding sub-levels.
     * @return string Top-level enumerated value native equivalent.
     */
    public function resolveDayStatus(array $allPeriodsStatus): string
    {
        if (empty($allPeriodsStatus)) return AttendanceReportStatus::NoPeriods->value;
        $unique = array_unique($allPeriodsStatus);
        
        if (count($unique) === 1) {
            $first = $unique[0];
            if ($first === AttendanceReportStatus::Future->value)  return AttendanceReportStatus::Future->value;
            if ($first === AttendanceReportStatus::Absent->value)  return AttendanceReportStatus::Absent->value;
            if ($first === AttendanceReportStatus::Present->value) return AttendanceReportStatus::Present->value;
        }
        
        return AttendanceReportStatus::Partial->value;
    }
}
