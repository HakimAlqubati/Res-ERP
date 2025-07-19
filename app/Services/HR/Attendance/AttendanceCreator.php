<?php
namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceCreator
{

    public $attendanceType = Attendance::ATTENDANCE_TYPE_RFID;
    protected AttendanceValidator $validator;
    public function __construct(AttendanceValidator $validator,
        protected CheckInHandler $checkInHandler,
        protected CheckOutHandler $checkOutHandler,
        protected CheckTypeDecider $checkTypeDecider
    ) {
        $this->validator = $validator;
    }

    public function handleOrCreateAttendance(
        $employee,
        $closestPeriod,
        string $date,
        string $time,
        string $day,
        array $existAttendance
    ) {
        if (isset($existAttendance['in_previous'])) {
            if ($existAttendance['in_previous']['check_type'] == Attendance::CHECKTYPE_CHECKIN) {

                return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKOUT, $existAttendance);
            } else {

                $endTime   = \Carbon\Carbon::parse($closestPeriod->end_at);
                $checkTime = \Carbon\Carbon::parse($time);

                if ($endTime->gt($checkTime)) {

                    return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKIN, $existAttendance);
                } else {
                    $message = __('notifications.attendance_time_is_greater_than_current_period_end_time') . ':(' . $closestPeriod?->name . ')';

                    return $message;
                }
            }
        }
        $typeHidden = true;
        // تحديد نوع الحضور عبر CheckTypeDecider
        $checkType = $this->checkTypeDecider->decide(
            $employee,
            $closestPeriod,
            $date,
            $time,
            $day,
            $existAttendance,
            $typeHidden,     // أو false حسب ما تريده هنا (يمكن تمريره كمرجع)
            $manualType = '' // أو قيمة `checkin/checkout` اليدوية إن وجدت
        );

// إذا كانت النتيجة رسالة نصية بدل نوع صالح
        if (! in_array($checkType, [Attendance::CHECKTYPE_CHECKIN, Attendance::CHECKTYPE_CHECKOUT])) {
            return [
                'success' => false,
                'message' => $checkType,
            ];
        }

// إنشاء الحضور
        $createdAttendance = $this->createAttendance(
            $employee,
            $closestPeriod,
            $date,
            $time,
            $day,
            $checkType,
            $existAttendance
        );
        if (isset($createdAttendance['success']) && ! $createdAttendance['success']) {
            return $createdAttendance;
        }
        if (isset($createdAttendance['success']) &&  $createdAttendance['success']) {
            return $createdAttendance;
        } 
        return [
            'success' => true,
            'data'    => $createdAttendance,
        ];

    }

    public function createAttendance(
        $employee,
        $nearestPeriod,
        string $date,
        string $checkTime,
        string $day,
        string $checkType,
        $previousRecord = null,
        bool $isRequest = false,
    ) {
        $checkTimeStr = $checkTime;
        // Ensure that $checkTime is a Carbon instance
        // $checkTime = \Carbon\Carbon::parse($checkTime);
        $checkTime = Carbon::parse($date . ' ' . $checkTime);

        $lastRecord = Attendance::where('created_at', '>=', Carbon::now()->subMinutes(1))->where('accepted', 1)->where('employee_id', $employee->id)->first();

        if ($lastRecord && ! $isRequest) {
            // // Calculate the remaining seconds until a new record can be created
            $remainingSeconds = Carbon::parse($lastRecord->created_at)->addMinutes(1)->diffInSeconds(Carbon::now());

            // Convert seconds to minutes and seconds
            $remainingMinutes = floor($remainingSeconds / 60);
            $remainingSeconds = $remainingSeconds % 60;
            $remainingMinutes *= -1;
            $remainingSeconds *= -1;
            $message = __('notifications.please_wait_for_a') . ' ' . $remainingMinutes . ' ' . __('notifications.minutue') . ' ' . $remainingSeconds . ' ' . __('notifications.second');

            return [
                'success' => false,
                'message' => $message,
            ];
        }

        // بيانات أولية للحضور
        $attendanceData = [
            'employee_id'     => $employee->id,
            'period_id'       => $nearestPeriod->id,
            'check_date'      => $date,
            'check_time'      => $checkTime,
            'day'             => $day,
            'check_type'      => $checkType,
            'branch_id'       => $employee?->branch?->id,
            'created_by'      => 0,
            'attendance_type' => $this->attendanceType,
        ];
        // فحص إذا من اليوم السابق
        if ($previousRecord && isset($previousRecord['in_previous'])) {
            $attendanceData['is_from_previous_day'] = 1;
            $attendanceData['check_date']           = $previousRecord['in_previous']?->check_date;
        }

        if ($checkType === Attendance::CHECKTYPE_CHECKIN) {
            $attendanceData['employee'] = $employee;
            $result                     = $this->checkInHandler->handle(
                $attendanceData,
                $nearestPeriod,
                $checkTime,
                $checkTimeStr,
                $day,
                $previousRecord
            );

            if (is_string($result)) {
                return [
                    'success' => false,
                    'message' => $result,
                ];
            }
            $attendanceData = $result;
        }
 
        if ($checkType === Attendance::CHECKTYPE_CHECKOUT) {
            $attendanceData = $this->checkOutHandler->handle($attendanceData, $nearestPeriod, $employee->id, $date, $checkTime, $previousRecord);

        }
        return $attendanceData;

    }
}