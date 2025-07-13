<?php
namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Enums\HR\Attendance\AttendanceReportStatus;

class HelperFunctions
{
    public static function calculateAttendanceStats($reportData)
    {
        $stats = [
            'present_days'    => 0,
            'absent_days'     => 0,
            'partial_days'    => 0,
            'no_periods_days' => 0,
            'leave_days'      => 0,
            'total_days'      => 0,
        ];

        foreach ($reportData as $date => $data) {
            // تجاهل statistics نفسها
            if ($date === 'statistics') {
                continue;
            }

            $stats['total_days']++;

            // احسب نوع اليوم
            switch ($data['day_status'] ?? null) {
                case AttendanceReportStatus::Present->value: $stats['present_days']++;
                    break;
                case AttendanceReportStatus::Absent->value: $stats['absent_days']++;
                    break;
                case AttendanceReportStatus::Partial->value: $stats['partial_days']++;
                    break;
                case AttendanceReportStatus::Leave->value: $stats['leave_days']++;
                    break;
                case AttendanceReportStatus::NoPeriods->value: $stats['no_periods_days']++;
                    break;

                default: $stats['no_periods_days']++;
                    break;
            }
        }

        return $stats;
    }
}