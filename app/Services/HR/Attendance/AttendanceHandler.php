<?php
namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\HR\MonthClosure\MonthClosureService;
use Carbon\Carbon;

class AttendanceHandler
{
    protected AttendanceValidator $validator;

    public $employeeId                = 0;
    public $date                      = '';
    public $nextDate                  = '';
    public $previousDate              = '';
    public string $realAttendanceDate = '';
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
        try {
            $dateTime = $data['date_time']; // e.g. "2025-07-07 14:30:00"

            $date                     = date('Y-m-d', strtotime($dateTime)); // "2025-07-07"
            $this->date               = $date;
            $this->realAttendanceDate = date('Y-m-d', strtotime($dateTime));
            $this->nextDate           = date('Y-m-d', strtotime("$date +1 day"));
            $this->previousDate       = date('Y-m-d', strtotime("$date -1 day"));
            $time                     = date('H:i:s', strtotime($dateTime)); // "14:30:00"
            $this->employeeId         = $employee->id;
            app(MonthClosureService::class)->ensureMonthIsOpen($date);
            $employeePeriods = $employee?->periods;
            // dd($employeePeriods);
            if (! is_null($employee) && count($employeePeriods) > 0) {
                $day = strtolower(Carbon::parse($date)->format('D'));

                $date = date('Y-m-d', strtotime($dateTime));
                $day  = strtolower(Carbon::parse($date)->format('D')); // الآن: "wed", "sun", إلخ

                $current         = Carbon::parse($date)->format('Y-m-d');
                $yesterday       = Carbon::parse($date)->subDay()->format('Y-m-d');
                $tomorrow        = Carbon::parse($date)->addDay()->format('Y-m-d');
                $employeePeriods = $employee->employeePeriods()
                // ->whereHas('days', function ($query) use ($yesterday, $tomorrow) {
                //     $query->where('start_date', '<=', $tomorrow)
                //         ->where(function ($q) use ($yesterday) {
                //             $q->whereNull('end_date')
                //                 ->orWhere('end_date', '>=', $yesterday);
                //         });
                // })
                    ->with(['days', 'workPeriod'])->get();
                // dd($employeePeriods); 
                $periodsForToday   = $this->getPeriodsForDate($employeePeriods, $date, $time);
                $periodsForPrevDay = $this->getPeriodsForDate($employeePeriods, $this->previousDate, $time);


                // دمج المصفوفتين
                $allPeriods = $periodsForToday->merge($periodsForPrevDay);
                // dd($allPeriods);
                $closestPeriod = $this->findClosestPeriod($time, $allPeriods);
                // dd($periodsForToday, $closestPeriod?->id,$closestPeriod, $this->date, $date);
                // Check if no periods are found for the given day
                if (! $closestPeriod) {
                    return
                        [
                        'success' => false,
                        // 'message' => __('notifications.cannot_check_in_because_adjust'),
                        'message' => __('notifications.you_dont_have_periods_today') . ' (' . $day . '-' . date('Y-m-d', strtotime($dateTime)) . ') ',
                        'data'    => $closestPeriod,
                    ];
                }
                if ($this->validator->isTimeOutOfAllowedRange($closestPeriod, $time)) {
                    return [
                        'success' => false,
                        'message' => __('notifications.cannot_check_in_because_adjust'),
                    ];

                }
                // dd($closestPeriod);
                $adjusted = AttendanceDateService::adjustDateForMidnightShift($date, $time, $closestPeriod);
                $date     = $adjusted['date'];
                $day      = $adjusted['day'];

                $existAttendance = AttendanceFetcher::getExistingAttendance($employee, $closestPeriod, $date, $day, $time);

                if (isset($existAttendance['in_previous'])) {
                    $date = $existAttendance['in_previous']->check_date;
                } else {
                    if ($date !== $this->date) {
                        // $date = $this->date;
                    }
                }

                $attendanceData = $this->attendanceCreator->handleOrCreateAttendance(
                    $employee,
                    $closestPeriod,
                    $date,
                    $time,
                    $day,
                    $existAttendance,
                    $this->realAttendanceDate);
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
        } catch (\Exception $e) {
            // هنا لو حصل أي Exception (مثل إقفال الشهر أو غيره)
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function findClosestPeriod(string $time, $periods, $forPrevDay = false)
    {
        $currentTime        = strtotime($time);
        $closest            = null;
        $minDiff            = null;
        $currentTimeObj     = Carbon::createFromFormat('Y-m-d H:i:s', "$this->date $time");
        $allowedHours       = (int) \App\Models\Setting::getSetting('hours_count_after_period_after');
        $allowedHoursBefore = (int) \App\Models\Setting::getSetting('hours_count_after_period_before', 1);
        foreach ($periods as $period) {
            $workPeriod = $period->workPeriod;
            if ($workPeriod->start_at === '00:00:00') {

                $nextDay = Carbon::parse($this->date)->addDay()->format('Y-m-d');

                if ($time >= '00:00:00' 
                && $time <= Carbon::createFromTimeString($workPeriod->end_at)->addHours($allowedHours)->format('H:i:s')) {
                    $nextDay = $this->date;
                }
                $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "$nextDay 00:00:00");
                $earliestStart = $startDateTime->copy()->subHours($allowedHoursBefore);

                $periodEndDateTime  = Carbon::parse("$nextDay $workPeriod->end_at");
                $allowedEndDateTime = $periodEndDateTime->copy()->addHours($allowedHours);

                if ($currentTimeObj->between($earliestStart, $startDateTime)) {
                    // dd($currentTime, $earliestStart, $startDateTime,$currentTime->between($earliestStart, $startDateTime));
                    // ✅ تعديل التاريخ: لأنه حضر في الليلة السابقة لفترة تبدأ 00:00
                    $this->date = $this->nextDate;

                    return $workPeriod;
                } elseif ($currentTimeObj->between($startDateTime, $allowedEndDateTime)) {
                    return $workPeriod;
                }
            }

            $dayAndNight = $workPeriod->day_and_night;
            $start       = strtotime($workPeriod->start_at);

            $end = strtotime($workPeriod->end_at);
            //     dd($time , Carbon::createFromTimeString($workPeriod->start_at)->subHours($allowedHoursBefore)->format('H:i:s'),
            // $time >= Carbon::createFromTimeString($workPeriod->start_at)->subHours($allowedHoursBefore)->format('H:i:s'));
            if ($time >= Carbon::createFromTimeString($workPeriod->start_at)->subHours($allowedHoursBefore)->format('H:i:s')
                && $time < '23:59:59'
            ) {
                $periodEndDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "$this->nextDate $workPeriod->end_at")->addHours($allowedHours);
                $earliestStart     = Carbon::createFromFormat('Y-m-d H:i:s', "$this->realAttendanceDate $workPeriod->start_at")->subHours($allowedHoursBefore);
            } elseif ($time >= '00:00:00' && $time <= Carbon::createFromTimeString($workPeriod->end_at)->addHours($allowedHours)->format('H:i:s')) {
                $periodEndDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "$this->realAttendanceDate $workPeriod->end_at")->addHours($allowedHours);
                $earliestStart     = Carbon::createFromFormat('Y-m-d H:i:s', "$this->previousDate $workPeriod->start_at")->subHours($allowedHoursBefore);
                $this->date        = $this->previousDate;
            } else {
                continue; // Skip this period if it doesn't match the time criteria
            }

            $end = strtotime($periodEndDateTime->format('H:i:s'));
            // dd($dayAndNight, !Attendance::isPeriodClosed($this->employeeId, $workPeriod->id, $this->date));
            if ($dayAndNight && ! Attendance::isPeriodClosed($this->employeeId, $workPeriod->id, $this->date)) {
            // if ($dayAndNight) {
                $condition = $currentTimeObj->between($earliestStart, $periodEndDateTime);
                if ($condition) {
                    return $workPeriod;
                }
                //     // فترة ليلية (تعبر منتصف الليل)
                //  dd($currentTimeObj,$periodEndDateTime,$this->nextDate);
                //     dd(strtotime('00:00:00'), $currentTime,$end,$periodEndDateTime
                // ,($currentTime >= strtotime('00:00:00')) , ($currentTime <= $end)
                // );
                //     if (($currentTime >= strtotime('00:00:00')) && ($currentTime <= $end)
                //     ) {
                //         return $workPeriod;
                //     }
                // return $workPeriod;
                continue;
                // return null;
            }

            $diff = min(abs($currentTime - $start), abs($currentTime - $end));

            if (is_null($minDiff) || $diff < $minDiff) {
                $minDiff = $diff;
                $closest = $workPeriod;
            }
        }
        return $closest;
    }

    protected function getPeriodsForDate($employeePeriods, $date, $time)
    {
        $day = strtolower(Carbon::parse($date)->format('D'));

        return $employeePeriods->filter(function ($period) use ($day, $date, $time) {
            foreach ($period->days as $dayRow) {
                $isDayOk  = $dayRow->day_of_week === $day;
                $isDateOk = $dayRow->start_date <= $date && (! $dayRow->end_date || $dayRow->end_date >= $date);

                if ($isDayOk && $isDateOk) {
                    return true;
                }
                // ✅ منطق السماح بالحضور المبكر حسب الإعدادات:
                if ($time !== null && $period->workPeriod->start_at === '00:00:00') {
                    $allowedHoursBefore = (int) \App\Models\Setting::getSetting('hours_count_after_period_before', 1);

                    $nextDay      = Carbon::parse($date)->addDay()->format('Y-m-d');
                    $startTime    = Carbon::createFromFormat('Y-m-d H:i:s', "$nextDay 00:00:00");
                    $earliestTime = $startTime->copy()->subHours($allowedHoursBefore);
                    $currentTime  = Carbon::createFromFormat('Y-m-d H:i:s', "$date $time");
                    if ($currentTime->between($earliestTime, $startTime)) {
                        return true;
                    }
                }
            }
            return false;
        });
    }

}