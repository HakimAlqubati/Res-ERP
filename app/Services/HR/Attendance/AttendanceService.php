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

    public function handle(
        array $formData,
        string $attendanceType = 'rfid'
    ): array {
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

    public function handleTwoDates(array $formData, string $attendanceType = 'rfid'): array
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

        // هنا تسجيل حضور وانصراف
        $responses = [];
        if (!empty($formData['check_in'])) {
            $responses['check_in'] = $this->handler->handleEmployeeAttendance(
                $employee,
                ['date_time' => $formData['check_in']],
                $attendanceType
            );
        }

        if (!empty($formData['check_out'])) {
            $responses['check_out'] = $this->handler->handleEmployeeAttendance(
                $employee,
                ['date_time' => $formData['check_out']],
                $attendanceType
            );
        }

        return [
            'success' => true,
            'message' => 'Attendance recorded successfully.',
            'data'    => $responses,
        ];
    }

    public function handleBulk(array $formData, string $attendanceType = 'rfid'): array
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

        $responses = [];

        // === حالة يوم واحد (check_in / check_out) ===
        if (!empty($formData['check_in']) || !empty($formData['check_out'])) {
            if (!empty($formData['check_in'])) {
                $responses['check_in'] = $this->handler->handleEmployeeAttendance(
                    $employee,
                    ['date_time' => $formData['check_in']],
                    $attendanceType
                );
            }
            if (!empty($formData['check_out'])) {
                $responses['check_out'] = $this->handler->handleEmployeeAttendance(
                    $employee,
                    ['date_time' => $formData['check_out']],
                    $attendanceType
                );
            }
        }

        // === حالة Bulk (from_date → to_date) ===
        if (!empty($formData['from_date']) && !empty($formData['to_date'])) {
            $from = \Carbon\Carbon::parse($formData['from_date']);
            $to   = \Carbon\Carbon::parse($formData['to_date']);

            $days = $from->diffInDays($to) + 1; // يشمل اليوم الأول والأخير

            for ($i = 0; $i < $days; $i++) {
                $date = $from->copy()->addDays($i)->toDateString();

                // check-in
                if (!empty($formData['check_in_time'])) {
                    $responses["bulk_check_in_$date"] = $this->handler->handleEmployeeAttendance(
                        $employee,
                        ['date_time' => $date . ' ' . $formData['check_in_time']],
                        $attendanceType
                    );
                }

                // check-out
                if (!empty($formData['check_out_time'])) {
                    $responses["bulk_check_out_$date"] = $this->handler->handleEmployeeAttendance(
                        $employee,
                        ['date_time' => $date . ' ' . $formData['check_out_time']],
                        $attendanceType
                    );
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Attendance recorded successfully.',
            'data'    => $responses,
        ];
    }
}
