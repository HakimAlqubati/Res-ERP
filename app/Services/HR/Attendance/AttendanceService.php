<?php

namespace App\Services\HR\Attendance;

use App\Models\AppLog;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
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

        if (isset($formData['attendance_type'])) {
            $attendanceType  = $formData['attendance_type'];
        }
        $res = $this->handler->handleEmployeeAttendance(
            $employee,
            $formData,
            $attendanceType,
        );
        // ⬇️ لو فشل التسجيل خزّنه كسجل غير مقبول باستخدام دالة المودل (بدون تعقيد)
        if (isset($res['success']) && $res['success'] === false) {
            // dd('sdf');
            $dt  = isset($formData['date_time']) ? \Carbon\Carbon::parse($formData['date_time']) : now();
            $date = $dt->toDateString();
            $time = $dt->format('H:i:s');
            $day  = strtolower($dt->format('D'));

            $msg  = (string) ($res['message'] ?? 'Attendance failed');

            AppLog::write(
                $msg,
                AppLog::LEVEL_WARNING,
                context: 'attendance',
                extra: [
                    'employee_id' => $employee->id,
                    'attendance_type' => $attendanceType,
                    'date' => $date,
                    'time' => $time,
                    'day'  => $day,
                ]
            );

            // Attendance::storeNotAccepted(
            //     $employee,
            //     $date,
            //     $time,
            //     $day,
            //     (string) ($res['message'] ?? 'Attendance failed'),
            //     $formData['period_id'] ?? null,    // إن لم تعرف الفترة اتركها null
            //     $attendanceType
            // );
        }

        return $res;

        // TODO: Replace this with actual attendance creation logic
        return [
            'success' => true,
            'message' => "Employee found: {$employee->name}",
        ];
    }


    public function handleTwoDates(array $formData, string $attendanceType = 'rfid'): array
    {
        DB::beginTransaction();

        try {
            $employee = null;

            if (isset($formData['employee']) && $formData['employee'] instanceof Employee) {
                $employee = $formData['employee'];
            } elseif (isset($formData['employee_id'])) {
                $employee = Employee::find($formData['employee_id']);
            } elseif (isset($formData['rfid'])) {
                $employee = Employee::where('rfid', $formData['rfid'])->first();
            }

            if (!$employee) {
                throw new \Exception('Employee not found.');
            }

            $responses = [];

            // تسجيل check-in
            if (!empty($formData['check_in'])) {
                $res = $this->handler->handleEmployeeAttendance(
                    $employee,
                    ['date_time' => $formData['check_in'], 'type' => 'checkin'], // 👈 نمرر النوع صراحة
                    $attendanceType
                );
                $responses['check_in'] = $res;
                $dt   = \Carbon\Carbon::parse($formData['check_in']);

                if (!$res['success']) {
                    AppLog::write(
                        'Check-in failed: ' . (string)($res['message'] ?? 'Unknown error'),
                        AppLog::LEVEL_WARNING,
                        context: 'attendance',
                        extra: [
                            'attempt'          => 'checkin',
                            'employee_id'      => $employee->id,
                            'date_time'        => $dt->toDateTimeString(),
                            'attendance_type'  => $attendanceType,
                            'form_payload'     => $formData,
                        ]
                    );

                    throw new \Exception("Check-in failed: " . $res['message']);
                }
            }

            // تسجيل check-out
            if (!empty($formData['check_out'])) {
                $res = $this->handler->handleEmployeeAttendance(
                    $employee,
                    ['date_time' => $formData['check_out'], 'type' => 'checkout'], // 👈 نمرر النوع صراحة
                    $attendanceType
                );
                $responses['check_out'] = $res;
                $dt   = \Carbon\Carbon::parse($formData['check_out']);

                if (!$res['success']) {
                    AppLog::write(
                        'Check-out failed: ' . (string)($res['message'] ?? 'Unknown error'),
                        AppLog::LEVEL_WARNING,
                        context: 'attendance',
                        extra: [
                            'attempt'          => 'checkout',
                            'employee_id'      => $employee->id,
                            'date_time'        => $dt->toDateTimeString(),
                            'attendance_type'  => $attendanceType,
                            'form_payload'     => $formData,
                        ]
                    );

                    throw new \Exception("Check-out failed: " . $res['message']);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Attendance recorded successfully.',
                'data'    => $responses,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }



    public function handleBulk(array $formData, string $attendanceType = 'rfid'): array
    {
        DB::beginTransaction();

        try {
            $employee = null;

            if (isset($formData['employee']) && $formData['employee'] instanceof Employee) {
                $employee = $formData['employee'];
            } elseif (isset($formData['employee_id'])) {
                $employee = Employee::find($formData['employee_id']);
            } elseif (isset($formData['rfid'])) {
                $employee = Employee::where('rfid', $formData['rfid'])->first();
            }

            if (!$employee) {
                throw new \Exception('Employee not found.');
            }

            $responses = [];

            // === حالة يوم واحد ===
            if (!empty($formData['check_in']) || !empty($formData['check_out'])) {
                if (!empty($formData['check_in'])) {
                    $responses['check_in'] = $this->handler->handleEmployeeAttendance(
                        $employee,
                        ['date_time' => $formData['check_in']],
                        $attendanceType
                    );
                    if (!$responses['check_in']['success']) {
                        throw new \Exception($responses['check_in']['message']);
                    }
                }
                if (!empty($formData['check_out'])) {
                    $responses['check_out'] = $this->handler->handleEmployeeAttendance(
                        $employee,
                        ['date_time' => $formData['check_out']],
                        $attendanceType
                    );
                    if (!$responses['check_out']['success']) {
                        throw new \Exception($responses['check_out']['message']);
                    }
                }
            }

            // === حالة Bulk ===
            if (!empty($formData['from_date']) && !empty($formData['to_date'])) {
                $from = \Carbon\Carbon::parse($formData['from_date']);
                $to   = \Carbon\Carbon::parse($formData['to_date']);

                $days = $from->diffInDays($to) + 1;

                for ($i = 0; $i < $days; $i++) {
                    $date = $from->copy()->addDays($i)->toDateString();

                    // check-in
                    if (!empty($formData['check_in_time'])) {
                        $res = $this->handler->handleEmployeeAttendance(
                            $employee,
                            [
                                'date_time' => $date . ' ' . $formData['check_in_time'],
                                'type'      => 'checkin'
                            ],
                            $attendanceType
                        );
                        $responses["bulk_check_in_$date"] = $res;
                        if (!$res['success']) {
                            throw new \Exception("Check-in failed for $date: " . $res['message']);
                        }
                    }

                    // check-out
                    if (!empty($formData['check_out_time'])) {
                        $res = $this->handler->handleEmployeeAttendance(
                            $employee,
                            [
                                'date_time' => $date . ' ' . $formData['check_out_time'],
                                'type'      => 'checkout'
                            ],
                            $attendanceType
                        );
                        $responses["bulk_check_out_$date"] = $res;
                        if (!$res['success']) {
                            throw new \Exception("Check-out failed for $date: " . $res['message']);
                        }
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Attendance recorded successfully.',
                'data'    => $responses,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }
}
