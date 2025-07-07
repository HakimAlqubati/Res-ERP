<?php

namespace App\Services\HR\Attendance;

use App\Models\Employee;

class AttendanceService
{
    protected AttendanceValidator $validator;
    protected AttendanceHandler $handler; 

    public function __construct(
        AttendanceValidator $validator,
        AttendanceHandler $handler, 
    ) {
        $this->validator = $validator;
        $this->handler = $handler; 
    }

    public function handle(array $formData, string $attendanceType = 'rfid'): array
    {
        $rfid = $formData['rfid'] ?? null;

        if (!$rfid) {
            return [
                'success' => false,
                'message' => 'RFID is required.',
            ];
        }

        $employee = Employee::where('rfid', $rfid)->first();

        if (!$employee) {
            return [
                'success' => false,
                'message' => 'Employee not found.',
            ];
        }
 
        return $this->handler->handleEmployeeAttendance($employee,$formData,$formData['date_time']); 

        // TODO: Replace this with actual attendance creation logic
        return [
            'success' => true,
            'message' => "Employee found: {$employee->name}",
        ];
    }
}