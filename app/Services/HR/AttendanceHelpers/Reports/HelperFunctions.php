<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Models\Attendance;

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
            'required_days' => 0,
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
                case AttendanceReportStatus::Present->value:
                    $stats['present_days']++;
                    $stats['required_days']++;
                    break;
                case AttendanceReportStatus::Absent->value:
                    $stats['absent']++;
                    $stats['required_days']++;
                    break;
                case AttendanceReportStatus::Partial->value:
                    $stats['partial']++;
                    $stats['required_days']++;
                    break;
                case AttendanceReportStatus::Leave->value:
                    $stats['leave']++;
                    $stats['required_days']++;
                    break;
                case AttendanceReportStatus::NoPeriods->value:
                    $stats['no_periods']++;
                    break;

                default:
                    $stats['no_periods']++;
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
            'labels' => array_map(fn($s) => $s->value, $statuses),
            'values' => array_map(fn($s) => $stats[$s->value] ?? 0, $statuses),
            'colors' => array_map(fn($s) => $s->hexColor(), $statuses),
        ];
        return [
            'chartData'     => $chartData,
            'employee_name' => $employee?->name ?? '',
            // باقي البيانات
        ];
    }

    public function calculateTotalLateArrival($attendanceData)
    {
        $totalDelayMinutes = 0;

        // Loop through each date in the attendance data
        foreach ($attendanceData as $date => $data) {
            if (isset($data['periods'])) {
                // Loop through each period for the date
                foreach ($data['periods'] as $period) {
                    if (isset($period['attendances']['checkin'])) {
                        // Loop through each checkin record 
                        // Check if the status is 'late_arrival'
                        if (isset($period['attendances']['checkin'][0]['status']) && $period['attendances']['checkin'][0]['status'] === Attendance::STATUS_LATE_ARRIVAL) {
                            // Add the delay minutes to the total
                            if ($period['attendances']['checkin'][0]['delay_minutes'] > settingWithDefault('early_attendance_minutes', 15)) {
                                if (setting('flix_hours')) {
                                    if (
                                        isset($period['attendances']['checkout']['lastcheckout']['supposed_duration_hourly']) &&
                                        $this->timeToHoursForLateArrival($period['attendances']['checkout']['lastcheckout']['total_actual_duration_hourly'])
                                        < $this->timeToHoursForLateArrival($period['attendances']['checkout']['lastcheckout']['supposed_duration_hourly'])
                                    ) {
                                        $totalDelayMinutes += $period['attendances']['checkin'][0]['delay_minutes'];
                                    }
                                } else {
                                    $totalDelayMinutes += $period['attendances']['checkin'][0]['delay_minutes'];
                                }
                            }
                        }
                    }
                }
            }
        }
        // dd($totalDelayMinutes);
        // Calculate total hours as a float
        $totalHoursFloat = $totalDelayMinutes / 60;

        return [
            'totalMinutes' => $totalDelayMinutes,
            'totalHoursFloat' => round($totalHoursFloat, 1),
        ];
    }

    protected function timeToHoursForLateArrival(string $time): float
    {
        // Check if the time is in "H:i:s" format
        if (preg_match('/^\d{1,2}:\d{1,2}:\d{1,2}$/', $time)) {
            $carbonTime = \Carbon\Carbon::createFromFormat('H:i:s', $time);

            return $carbonTime->hour
                + ($carbonTime->minute / 60)
                + ($carbonTime->second / 3600);
        }

        // Check if the time is in "X h Y m" format
        if (preg_match('/(\d+)\s*h\s*(\d*)\s*m*/i', $time, $matches)) {
            $hours = isset($matches[1]) ? (int) $matches[1] : 0;
            $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
            $minutes +=  setting('early_attendance_minutes');

            return $hours + ($minutes / 60);
        }

        // If format is invalid
        throw new \InvalidArgumentException("Invalid time format. Expected 'H:i:s' or 'X h Y m'.");
    }

   public function calculateMissingHours(
        $status,
        $supposedDuration,
        $approvedOvertime,
        $date,
        $employeeId
    ) { 

        $isMultiple = Attendance::selectRaw('period_id, COUNT(*) as total')
            ->where('check_date', $date)
            ->where('employee_id', $employeeId)
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->groupBy('period_id')
            ->having('total', '>', 1)
            ->exists();

        if (!$isMultiple) {
            return [
                'formatted' => '0 h 0m',
                'total_minutes' => 0,
            ];
        }
        if (in_array($status, [
            Attendance::STATUS_EARLY_DEPARTURE,
            Attendance::STATUS_LATE_ARRIVAL
        ])) {
            // return [
            //     'formatted' => '0 h 0m',
            //     'total_minutes' => 0,
            // ];
        }
        // Default the supposed duration if null
        $supposedDuration = $supposedDuration ?? '00:00:00';


        $approvedOvertimeParsed = convertToFormattedTime($approvedOvertime);
        // dd($approvedOvertimeParsed);
        if (!\Carbon\Carbon::parse($approvedOvertimeParsed)->lt(\Carbon\Carbon::parse($supposedDuration))) {
            // dd(\Carbon\Carbon::parse($supposedDuration), \Carbon\Carbon::parse($approvedOvertimeParsed));
            return [
                'formatted' => '0 h 0m',
                'total_minutes' => 0,
            ];
        }
        // Calculate the difference
        $difference = \Carbon\Carbon::parse($supposedDuration)->diff(\Carbon\Carbon::parse($approvedOvertimeParsed));

        // Calculate the total number of minutes
        $totalMinutes = $difference->h * 60 + $difference->i;
        $totalHours = round($totalMinutes / 60, 1);
        // Return both formatted difference and total minutes in an array
        return [
            'formatted' => $difference->format('%h h %i m'),
            'total_minutes' => $totalMinutes,
            'total_hours' => $totalHours,
        ];
        // Return the formatted difference
        return $difference->format('%h h %i m');
    }
}
