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
        bool $isRequest = false
    ) {
        $checkTimeStr = $checkTime;
        $checkTime    = Carbon::parse("$date $checkTime");

        // منع تكرار التسجيل خلال 15 دقيقة
        // $lastRecord = Attendance::where('employee_id', $employee->id)
        //     ->where('accepted', 1)
        //     ->where('created_at', '>=', Carbon::now()->subMinutes(15))
        //     ->first();

        $newCheckDateTime = $date . ' ' . $checkTimeStr;
        $thresholdTime    = Carbon::parse($newCheckDateTime)->subMinutes(15)->format('Y-m-d H:i:s');

        $lastRecord = Attendance::where('employee_id', $employee->id)
            ->where('accepted', 1)
            ->whereRaw("STR_TO_DATE(CONCAT(check_date, ' ', check_time), '%Y-%m-%d %H:%i:%s') >= ?", [$thresholdTime])
            ->orderByDesc('check_date')
            ->orderByDesc('check_time')
            ->first();

        if ($lastRecord && ! $isRequest) {
            // استخدم check_date + check_time معًا لآخر سجل
            $lastCheckDateTime = Carbon::parse($lastRecord->check_date . ' ' . $lastRecord->check_time);
            $nextAllowedTime   = $lastCheckDateTime->copy()->addMinutes(15);

                                        // الوقت الذي يحاول المستخدم التسجيل فيه
            $newCheckTime = $checkTime; // من الكود الأصلي، وهو فعلاً Carbon لـ (التاريخ + الوقت)

            // إذا لم تمر 15 دقيقة بعد، حساب الوقت المتبقي
            if ($nextAllowedTime->gt($newCheckTime)) {
                $remainingSeconds = $nextAllowedTime->diffInSeconds($newCheckTime, false) * -1; // القيمة سالبة لو لم تمر الفترة
                $remainingMinutes = floor($remainingSeconds / 60);
                $remainingSeconds = $remainingSeconds % 60;

                $message = __('notifications.please_wait_for_a') . ' ' .
                abs($remainingMinutes) . ' ' . __('notifications.minute') . ' ' .
                abs($remainingSeconds) . ' ' . __('notifications.second');

                $message = __('notifications.please_wait_for_a') . ' ' . $remainingMinutes . ' ' .
                __('notifications.minutue') . ' ' . $remainingSeconds . ' ' . __('notifications.second');

                return [
                    'success' => false,
                    'message' => $message,
                ];

            }
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