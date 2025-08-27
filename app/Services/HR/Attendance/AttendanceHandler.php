<?php
namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceHandler
{
    protected AttendanceValidator $validator;

    public $attendanceType = Attendance::ATTENDANCE_TYPE_RFID;
    public function __construct(AttendanceValidator $validator,
        protected CheckInHandler $checkInHandler,
        protected CheckOutHandler $checkOutHandler,
        protected CheckTypeDecider $checkTypeDecider,
        protected AttendanceSaver $attendanceSaver
    ) {
        $this->validator = $validator;
    }

    public function handleEmployeeAttendance(Employee $employee,
        array $data): array {

        $dateTime = $data['date_time']; // e.g. "2025-07-07 14:30:00"

        $date = date('Y-m-d', strtotime($dateTime)); // "2025-07-07"
        $time = date('H:i:s', strtotime($dateTime)); // "14:30:00"

        $employeePeriods = $employee?->periods;
        if (! is_null($employee) && count($employeePeriods) > 0) {
            $day = Carbon::parse($date)->format('l');

            // Decode the days array for each period
            $workTimePeriods = $employee->periods->map(function ($period) {
                $period->days = json_decode($period->days); // Ensure days are decoded
                return $period;
            });

            // Filter periods by the day
            $periodsForDay = $workTimePeriods->filter(function ($period) use ($day) {
                return in_array($day, $period->days);
            });
            $closestPeriod = $this->findClosestPeriod($time, $periodsForDay);

            // Check if no periods are found for the given day
            if ($periodsForDay->isEmpty()) {
                return
                    [
                    'success' => false,
                    'message' => __('notifications.you_dont_have_periods_today') . ' (' . $day . ')',
                ];
            }
            if ($this->validator->isTimeOutOfAllowedRange($closestPeriod, $time)) {
                return [
                    'success' => false,
                    'message' => __('notifications.cannot_check_in_because_adjust'),
                ];

            }

            if ($closestPeriod) {

                // return [
                //     'success'        => true,
                //     'closest_period' => $closestPeriod,
                //     'message'        => '',
                // ];
            }

            $adjusted = AttendanceDateService::adjustDateForMidnightShift($date, $time, $closestPeriod);
            $date     = $adjusted['date'];
            $day      = $adjusted['day'];

            $existAttendance = AttendanceFetcher::getExistingAttendance($employee, $closestPeriod, $date, $day, $time);

            $attendanceData = $this->handleOrCreateAttendance(
                $employee,
                $closestPeriod,
                $date,
                $time,
                $day,
                $existAttendance);

            if (is_array($attendanceData) && isset($attendanceData['success']) && $attendanceData['success'] === false) {
                return $attendanceData;
            }
 
            if(!$attendanceData['success']){
                return $attendanceData;
            }
            $checkType = $attendanceData['check_type'] ?? null;
            $message   = match ($checkType) {
                Attendance::CHECKTYPE_CHECKIN => __('notifications.check_in_success'),
                Attendance::CHECKTYPE_CHECKOUT => __('notifications.check_out_success'),
                default => __('notifications.attendance_success'),
            };

            
            
// ✳️ الحفظ باستخدام AttendanceSaver
            $attendanceRecord = $this->attendanceSaver->save($attendanceData);

            return
                ['success' => true,
                'data'     => $attendanceRecord,
                    'message' => $message,

            ];

        } elseif (! is_null($employee) && count($employeePeriods) == 0) {

            return
                [
                'success' => false,
                'message' => __('notifications.sorry_no_working_hours_have_been_added_to_you_please_contact_the_administration'),
            ];
        } else {
            return
                [
                'success' => false,
                'message' => __('notifications.there_is_no_employee_at_this_number'),
            ];
        }
    }

    protected function findClosestPeriod(string $time, $periods)
    {
        $currentTime = strtotime($time);
        $closest     = null;
        $minDiff     = null;

        foreach ($periods as $period) {
            $start = strtotime($period->start_at);
            $end   = strtotime($period->end_at);

            $diff = min(abs($currentTime - $start), abs($currentTime - $end));

            if (is_null($minDiff) || $diff < $minDiff) {
                $minDiff = $diff;
                $closest = $period;
            }
        }

        return $closest;
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

                $endTime   = Carbon::parse($closestPeriod->end_at);
                $checkTime = Carbon::parse($time);

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
        $lastRecord = Attendance::where('employee_id', $employee->id)
            ->where('accepted', 1)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($lastRecord && ! $isRequest) {
            $remainingSeconds = Carbon::parse($lastRecord->created_at)->addMinutes(15)->diffInSeconds(Carbon::now());
            $remainingMinutes = floor($remainingSeconds / 60) * -1;
            $remainingSeconds %= 60;
            $remainingSeconds *= -1;

            $message = __('notifications.please_wait_for_a') . ' ' . $remainingMinutes . ' ' .
            __('notifications.minutue') . ' ' . $remainingSeconds . ' ' . __('notifications.second');

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