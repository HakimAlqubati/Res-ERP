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
        protected AttendanceCreator $attendanceCreator,
        protected PeriodHelper $periodHelper,
    ) {
        $this->validator    = $validator;
        $this->periodHelper = $periodHelper;
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

            $employeePeriods   = $employee->employeePeriods()->with(['days', 'workPeriod'])->get();
            $prevDate          = date('Y-m-d', strtotime("$date -1 day"));
            $periodsForToday   = $this->getPeriodsForDate($employeePeriods, $date);
            $periodsForPrevDay = $employeePeriods->filter(function ($period) use ($prevDate) {
                return $period->workPeriod->day_and_night && // فقط الفترات الليلية
                $this->periodHelper->periodCoversDate($period, $prevDate);
            });

            if($periodsForPrevDay->count()>0 && $periodsForToday->count()<=0){
                $date= $prevDate; 
            }
// دمج المصفوفتين
            $allPeriods    = $periodsForToday->merge($periodsForPrevDay);
            $closestPeriod = $this->findClosestPeriod($time, $allPeriods);
            // dd($closestPeriod, $day, $date, $employeePeriods);
            // if (! $closestPeriod) {
            //     $prevDate          = date('Y-m-d', strtotime("$date -1 day"));
            //     $periodsForPrevDay = $this->getPeriodsForDate($employeePeriods, $prevDate);
            //     $closestPeriod     = $this->findClosestPeriod($time, $periodsForPrevDay, true);
            //     $date              = $prevDate;
            //     $day               = strtolower(Carbon::parse($date)->format('D'));
            // }

            // dd($closestPeriod?->name,$day,$date);
            // Check if no periods are found for the given day
            if (! $closestPeriod) {
                return
                    [
                    'success' => false,
                    'message' => __('notifications.you_dont_have_periods_today') . ' (' . $day . '-' . $date . ') ',
                    'data'    => $closestPeriod,
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
            if (is_array($attendanceData) && isset($attendanceData['success']) && ! $attendanceData['success']) {
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

    protected function findClosestPeriod(string $time, $periods, $forPrevDay = false)
    {
        $currentTime = strtotime($time);
        $closest     = null;
        $minDiff     = null;

        foreach ($periods as $period) {
            $workPeriod = $period->workPeriod;

            $dayAndNight = $workPeriod->day_and_night;
            $start       = strtotime($workPeriod->start_at);
            $end         = strtotime($workPeriod->end_at);

            if ($dayAndNight) {
                // فترة ليلية (تعبر منتصف الليل)
                if (($currentTime >= strtotime('00:00:00')) && ($currentTime <= $end)) {
                    return $workPeriod;
                }
                return null;
            }
            $diff = min(abs($currentTime - $start), abs($currentTime - $end));

            if (is_null($minDiff) || $diff < $minDiff) {
                $minDiff = $diff;
                $closest = $workPeriod;
            }
        }

        return $closest;
    }

    protected function getPeriodsForDate($employeePeriods, $date)
    {
        $day = strtolower(Carbon::parse($date)->format('D'));
        return $employeePeriods->filter(function ($period) use ($day, $date) {
            foreach ($period->days as $dayRow) {
                $isDayOk  = $dayRow->day_of_week === $day;
                $isDateOk = $dayRow->start_date <= $date && (! $dayRow->end_date || $dayRow->end_date >= $date);
                if ($isDayOk && $isDateOk) {
                    return true;
                }
            }
            return false;
        });
    }

}