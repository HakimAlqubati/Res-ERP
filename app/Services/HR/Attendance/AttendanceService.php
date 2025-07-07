<?php

namespace App\Services\HR\Attendance;

use App\Models\Employee;

class AttendanceService
{
    protected AttendanceValidator $validator;
    protected AttendanceCreator $creator;
    protected AttendanceNotifier $notifier;

    public function __construct(
        AttendanceValidator $validator,
        AttendanceCreator $creator,
        AttendanceNotifier $notifier,
    ) {
        $this->validator = $validator;
        $this->creator = $creator;
        $this->notifier = $notifier;
    }

    public function handle(array $formData, string $attendanceType = 'rfid')
    {
        $rfid = $formData['rfid'] ?? null;
        if (!$rfid) {
            return $this->notifier->warning('RFID is required');
        }

        $employee = Employee::where('rfid', $rfid)->first();
        if (!$employee) {
            return $this->notifier->warning(__('notifications.there_is_no_employee_at_this_number'));
        }

        return $this->creator->handleEmployeeAttendance($employee, $formData, $attendanceType);
    }
}