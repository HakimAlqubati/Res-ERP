<?php

namespace App\Services\HR\Attendance;

use App\Models\Employee;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    protected AttendanceValidator $validator;
    protected AttendanceHandler $handler;

    public function __construct(
        AttendanceValidator $validator,
        AttendanceHandler $handler,
    ) {
        $this->validator = $validator;
        $this->handler   = $handler;
    }

    public function handle(array $formData,
     string $attendanceType = 'rfid'): array
    {
        $employee = null;
        if (isset($formData['employee']) && $formData['employee'] instanceof Employee) {
            $employee = $formData['employee'];
        } elseif (isset($formData['employee_id'])) {
            $employee = Employee::find($formData['employee_id']);
        } elseif (isset($formData['rfid'])) {
            $employee = Employee::where('rfid', $formData['rfid'])->first();
        }

        if (! $employee) {
            return [
                'success' => false,
                'message' => 'Employee not found.',
            ];
        }

        Log::alert('zxc', [$employee, $formData, $formData['date_time']]);
        return $this->handler->handleEmployeeAttendance(
            $employee,
            $formData,
            $attendanceType,
        );

        // TODO: Replace this with actual attendance creation logic
        return [
            'success' => true,
            'message' => "Employee found: {$employee->name}",
        ];
    }
}
