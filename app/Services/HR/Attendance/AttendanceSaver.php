<?php

namespace App\Services\HR\Attendance;

use App\Models\Attendance;

class AttendanceSaver
{
    public function save(array $attendanceData): Attendance
    {
        return Attendance::create([
            'employee_id'                  => $attendanceData['employee_id'],
            'period_id'                    => $attendanceData['period_id'],
            'check_date'                   => $attendanceData['check_date'],
            'check_time'                   => $attendanceData['check_time'],
            'day'                          => $attendanceData['day'],
            'check_type'                   => $attendanceData['check_type'],
            'branch_id'                    => $attendanceData['branch_id'] ?? null,
            'created_by'                   => $attendanceData['created_by'] ?? 0,
            'attendance_type'              => $attendanceData['attendance_type'] ,
            'status'                       => $attendanceData['status'] ,
            'actual_duration_hourly'       => $attendanceData['actual_duration_hourly'] ?? null,
            'supposed_duration_hourly'     => $attendanceData['supposed_duration_hourly'] ?? null,
            'total_actual_duration_hourly' => $attendanceData['total_actual_duration_hourly'] ?? null,
            'delay_minutes'                => $attendanceData['delay_minutes'] ?? 0,
            'early_arrival_minutes'        => $attendanceData['early_arrival_minutes'] ?? 0,
            'early_departure_minutes'      => $attendanceData['early_departure_minutes'] ?? 0,
            'late_departure_minutes'       => $attendanceData['late_departure_minutes'] ?? 0,
            'checkinrecord_id'             => $attendanceData['checkinrecord_id'] ?? null,
            'is_from_previous_day'         => $attendanceData['is_from_previous_day'] ?? 0,
            'note'                         => $attendanceData['note'] ?? null,
        ]);
    }
}