<?php
namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Enums\HR\Attendance\AttendanceReportStatus;

class HelperFunctions
{
    public static function calculateAttendanceStats($reportData)
    {
        $stats = [
            'present_days' => 0,
            'absent'       => 0,
            'partial'      => 0,
            'no_periods'   => 0,
            'leave'        => 0,
            'total_days'   => 0,
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
                case AttendanceReportStatus::Absent->value: $stats['absent']++;
                    break;
                case AttendanceReportStatus::Partial->value: $stats['partial']++;
                    break;
                case AttendanceReportStatus::Leave->value: $stats['leave']++;
                    break;
                case AttendanceReportStatus::NoPeriods->value: $stats['no_periods']++;
                    break;

                default: $stats['no_periods']++;
                    break;
            }
        }

        return $stats;
    }

    public static function getAttendanceChartData($reportData, $employee = null)
    { 
        $statuses = [
            AttendanceReportStatus::Present,
            AttendanceReportStatus::Absent,
            AttendanceReportStatus::Partial,
            AttendanceReportStatus::Leave,
            AttendanceReportStatus::NoPeriods,
        ];
        $stats = self::calculateAttendanceStats($reportData);

        // dd($stats,$statuses);
        $chartData = [
            'labels' => array_map(fn($s) => $s->label(), $statuses),
            'values' => array_map(fn($s) => $stats[$s->value] ?? 0, $statuses),
            'colors' => array_map(fn($s) => $s->hexColor(), $statuses),
        ];
        return [
            'chartData'     => $chartData,
            'employee_name' => $employee?->name ?? '',
            // باقي البيانات
        ];
    }
}