<?php

namespace App\Services\HR\AttendanceHelpers\Reports\DTOs;

/**
 * Represents a single employee who is currently present at work.
 */
final readonly class PresentEmployeeDTO implements \JsonSerializable
{
    public function __construct(
        public int     $employeeId,
        public ?string $employeeName,
        public ?int    $branchId,
        public string  $checkinTime,
        public string  $checkinDate,
        public int     $attendanceId,
        public ?int    $periodId,
        public ?string $periodName,
        public ?string $periodStartAt,
        public ?string $periodEndAt,
        public ?string $status,
    ) {}

    public static function fromAttendance(\App\Models\Attendance $attendance): self
    {
        return new self(
            employeeId: $attendance->employee_id,
            employeeName: $attendance->employee?->name,
            branchId: $attendance->employee?->branch_id,
            checkinTime: $attendance->check_time,
            checkinDate: $attendance->check_date,
            attendanceId: $attendance->id,
            periodId: $attendance->period_id,
            periodName: $attendance->period?->name,
            periodStartAt: $attendance->period?->start_at,
            periodEndAt: $attendance->period?->end_at,
            status: $attendance->status,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'employee_id'     => $this->employeeId,
            'employee_name'   => $this->employeeName,
            'branch_id'       => $this->branchId,
            'checkin_time'    => $this->checkinTime,
            'checkin_date'    => $this->checkinDate,
            'attendance_id'   => $this->attendanceId,
            'period_id'       => $this->periodId,
            'period_name'     => $this->periodName,
            'period_start_at' => $this->periodStartAt,
            'period_end_at'   => $this->periodEndAt,
            'status'          => $this->status,
        ];
    }
}
