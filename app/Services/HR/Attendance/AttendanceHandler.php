<?php
namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceHandler
{
    protected AttendanceValidator $validator;

    public function __construct(AttendanceValidator $validator,
        protected CheckInHandler $checkInHandler,
        protected CheckOutHandler $checkOutHandler,
        protected AttendanceSaver $attendanceSaver,
        protected AttendanceCreator $attendanceCreator
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
            $day = strtolower(Carbon::parse($date)->format('D'));

            // Decode the days array for each period

            $workTimePeriods = $employee->periods->map(function ($period) {

                return $period;
            });

            $date = date('Y-m-d', strtotime($dateTime));
            $day  = strtolower(Carbon::parse($date)->format('D')); // الآن: "wed", "sun", إلخ

            $employeePeriods = $employee->employeePeriods()->with(['days', 'workPeriod'])->get();
            
            $periodsForDay = $employeePeriods->filter(function ($period) use ($day, $date) {
                
                foreach ($period->days as $dayRow) {
                    $isDayOk  = $dayRow->day_of_week === $day; // الآن يقارن بالاختصار
                    
                    $isDateOk = $dayRow->start_date <= $date && (! $dayRow->end_date || $dayRow->end_date >= $date);
                    if ($isDayOk && $isDateOk) {
                        return true;
                    }
                }
                return false;
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

                // dd($closestPeriod);
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
 
            $attendanceData = $this->attendanceCreator->handleOrCreateAttendance(
                $employee,
                $closestPeriod,
                $date,
                $time,
                $day,
                $existAttendance);

            if (is_array($attendanceData) && isset($attendanceData['success']) && $attendanceData['success'] === false) {
                return $attendanceData;
            }
 
            if (! $attendanceData['success']) {
                return $attendanceData;
            }
            $checkType = $attendanceData['check_type'] ?? null;
            $message   = match ($checkType) {
                Attendance::CHECKTYPE_CHECKIN => __('notifications.check_in_success'),
                Attendance::CHECKTYPE_CHECKOUT => __('notifications.check_out_success'),
                default => __('notifications.attendance_success'),
            };  
// ✳️ الحفظ باستخدام AttendanceSaver
            $attendanceRecord = $this->attendanceSaver->save($attendanceData['data']);

            return
                ['success' => true,
                'data'     => $attendanceRecord,
                'message'  => $message,

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
            $period = $period->workPeriod;
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

}