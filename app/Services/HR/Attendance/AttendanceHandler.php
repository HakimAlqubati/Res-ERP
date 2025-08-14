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
    public $targetDate                      = '';
    public $targetDay = '';
    public $nextDate                  = '';
    public $previousDate              = '';
    public string $realAttendanceDate = '';
    public $workPeriod = null;
    public $day = '';
    public bool $hasWorkPeriod        = false;
    public function __construct(
        AttendanceValidator $validator,
        protected CheckInHandler $checkInHandler,
        protected CheckOutHandler $checkOutHandler,
        protected AttendanceSaver $attendanceSaver,
        protected AttendanceCreator $attendanceCreator,
        protected PeriodHelper $periodHelper,
    ) {
        $this->validator    = $validator;
        $this->periodHelper = $periodHelper;
    }

    public function handleEmployeeAttendance(
        Employee $employee,
        array $data
    ): array {
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

            if ($employee) {

                $latstCheckIn = Attendance::where('employee_id', $this->employeeId)->with('period')
                    ->where('accepted', 1)
                    ->select('id', 'check_type', 'period_id', 'check_date', 'check_time')
                    ->latest('id')->where('check_date', '>=', $this->previousDate)
                    ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->first();

                if ($latstCheckIn) {
                    $isClosed =    Attendance::isPeriodClosed(
                        $this->employeeId,
                        $latstCheckIn->period_id,
                        $latstCheckIn->check_date,
                        $latstCheckIn->id
                    );
                    dd($isClosed,$latstCheckIn);
                    if (!$isClosed) {
                        $this->workPeriod = $latstCheckIn->period;
                        if ($this->workPeriod) {
                            $allowedHours       = (int) \App\Models\Setting::getSetting('hours_count_after_period_after');
                            $endTimeWithALlowedTime = Carbon::createFromTimeString($this->workPeriod->end_at)->addHours($allowedHours)->format('H:i:s');
                            $workPeriodStartTime = $this->workPeriod->start_at;
                            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', "$latstCheckIn->check_date $workPeriodStartTime");

                            $currentTime = Carbon::createFromFormat('Y-m-d H:i:s', "$date $time");
                            $periodEndDateTime = Carbon::parse("$date $endTimeWithALlowedTime");
                            if ($currentTime->between($startTime, $periodEndDateTime)) {
                                $this->hasWorkPeriod = true;
                                $this->workPeriod = $latstCheckIn->period;
                                $this->day = strtolower(Carbon::parse($latstCheckIn->check_date)->format('D'));
                            }
                        }
                    }
                }
            }
            $employeePeriods = $employee?->periods;
            // dd($employeePeriods);
            if (! is_null($employee) && count($employeePeriods) > 0) {
                $day = strtolower(Carbon::parse($date)->format('D'));



                // $date = date('Y-m-d', strtotime($dateTime));
                // الآن: "wed", "sun", إلخ

                $minDate = min($date, $this->previousDate, $this->nextDate);
                $maxDate = max($date, $this->previousDate, $this->nextDate);

                $employeePeriods = $employee->employeePeriods()
                    ->with(['days', 'workPeriod'])
                    ->where('start_date', '<=', $maxDate)
                    ->where(function ($q) use ($minDate) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $minDate);
                    })
                    ->get();


                $potentialPeriods = collect();
                $dates = [$date, $this->previousDate, $this->nextDate];


                if (!$this->hasWorkPeriod) {
                    foreach ($dates as $targetDate) {
                        $periods = $this->getPeriodsForDate($employeePeriods, $targetDate);

                        // dd($periods,$targetDate);
                        $day     = strtolower(Carbon::parse($targetDate)->format('D'));
                        foreach ($periods as $period) {
                            $workPeriod = $period->workPeriod;

                            if (!$workPeriod) {
                                continue;
                            }

                            // ✅ الآن التحقق بحسب targetDate وليس this->date
                            $isActive = $this->periodHelper->hasWorkPeriodForDate($employee->id, $workPeriod->id, $targetDate, $day);

                            if (! $isActive) {
                                continue;
                            }

                            $allowedHours       = (int) \App\Models\Setting::getSetting('hours_count_after_period_after');
                            $endTimeWithALlowedTime = Carbon::createFromTimeString($workPeriod->end_at)->addHours($allowedHours)->format('H:i:s');
                            $periodEndDateTime = Carbon::parse("$date $endTimeWithALlowedTime");
                            $currentTimeByTargetDate = Carbon::createFromFormat('Y-m-d H:i:s', "$targetDate $time");

                            // dd($time);
                            $this->targetDate = $targetDate; // تحديث التاريخ المستهدف
                            $this->targetDay = strtolower(Carbon::parse($targetDate)->format('D'));
                            if (
                                $workPeriod->start_at === '00:00:00' ||
                                $workPeriod->day_and_night ||
                                $targetDate === $date
                            ) {
                                $potentialPeriods->push($period);
                            } elseif (
                                $date > $targetDate &&
                                $currentTimeByTargetDate->lessThan($periodEndDateTime)
                            ) {
                                $potentialPeriods->push($period);
                            }
                        }
                    }
                    // ✅ الآن نستخدم فقط الفترات الصحيحة والمنطقية
                    // $date = $this->date;
                    // dd($potentialPeriods);
                    $closestPeriod = $this->findClosestPeriod($time, $potentialPeriods);
                    $day = strtolower(Carbon::parse($this->date)->format('D'));
                } else {
                    $day = $this->day;
                    $closestPeriod = $this->workPeriod;
                }

                // dd(
                //     $closestPeriod?->name,
                //     $this->date,
                //     $day,
                //     'targetDate: ' . $this->targetDate
                // );

                // $closestPeriod = $this->findClosestPeriod($time, $potentialPeriods);

                if ($closestPeriod) {
                    // dd(
                    //     $closestPeriod?->name,
                    //     //  $closestPeriod,
                    //     $this->date,
                    //     $day,
                    //     $this->periodHelper->hasWorkPeriodForDate($employee->id, $closestPeriod->id, $this->date, $day),
                    //     'targetDate: ' . $this->targetDate,
                    // );
                    $this->hasWorkPeriod = $this->periodHelper->hasWorkPeriodForDate($employee->id, $closestPeriod->id, $this->date, $day);
                }
                // Check if no periods are found for the given day
                //    dd($this->hasWorkPeriod);
                if (! $closestPeriod || ! $this->hasWorkPeriod) {

                    $message = __('notifications.you_dont_have_periods_today') . ' (' . $day . '-' . date('Y-m-d', strtotime($dateTime)) . ') ';
                    if ($closestPeriod) {
                        Attendance::storeNotAccepted($employee, $this->date, $time, $day, $message, $closestPeriod->id, Attendance::ATTENDANCE_TYPE_RFID);
                    }
                    return
                        [
                            'success' => false,
                            // 'message' => __('notifications.cannot_check_in_because_adjust'),
                            'message' => $message,
                            // 'data'    => $closestPeriod,
                        ];
                }
                if ($this->validator->isTimeOutOfAllowedRange($closestPeriod, $time)) {
                    $message = __('notifications.cannot_check_in_because_adjust');
                    Attendance::storeNotAccepted($employee, $this->date, $time, $day, $message, $closestPeriod->id, Attendance::ATTENDANCE_TYPE_RFID);
                    return [
                        'success' => false,
                        'message' => $message,
                    ];
                }
                // dd($closestPeriod);
                // $adjusted = AttendanceDateService::adjustDateForMidnightShift($date, $time, $closestPeriod);
                // $date     = $adjusted['date'];
                // $day      = $adjusted['day'];

                // dd($date, $this->date,$closestPeriod?->name);
                $existAttendance = AttendanceFetcher::getExistingAttendance($employee, $closestPeriod, $this->date, $day, $time);
                // dd($existAttendance);
                if (isset($existAttendance['in_previous'])) {
                    $this->date = $existAttendance['in_previous']->check_date;
                } else {
                    if ($date !== $this->date) {
                        // $date = $this->date;
                    }
                }

                $attendanceData = $this->attendanceCreator->handleOrCreateAttendance(
                    $employee,
                    $closestPeriod,
                    $this->date,
                    $time,
                    $day,
                    $existAttendance,
                    $this->realAttendanceDate
                );
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
                    [
                        'success' => true,
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

    protected function findClosestPeriod(string $time, $periods)
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

                if (
                    $time >= '00:00:00'
                    && $time <= Carbon::createFromTimeString($workPeriod->end_at)->addHours($allowedHours)->format('H:i:s')
                ) {
                    $startDateTime     = Carbon::createFromFormat('Y-m-d H:i:s', "$this->date 00:00:00");
                    $periodEndDateTime = Carbon::parse("$this->date $workPeriod->end_at");
                } elseif (
                    $time >= Carbon::createFromTimeString($workPeriod->start_at)->subHours($allowedHoursBefore)->format('H:i:s')
                    && $time <= '23:59:59'
                ) {
                    $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "$this->nextDate 00:00:00");
                    $periodEndDateTime = Carbon::parse("$this->nextDate $workPeriod->end_at");
                    $this->date = $this->nextDate;
                } else {
                    continue; // Skip this period if it doesn't match the time criteria
                }
                $earliestStart = $startDateTime->copy()->subHours($allowedHoursBefore);

                $allowedEndDateTime = $periodEndDateTime->copy()->addHours($allowedHours);

                if ($currentTimeObj->between($earliestStart, $startDateTime)) {
                    // dd($currentTime, $earliestStart, $startDateTime,$currentTime->between($earliestStart, $startDateTime));
                    // ✅ تعديل التاريخ: لأنه حضر في الليلة السابقة لفترة تبدأ 00:00

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
            if (
                $time >= Carbon::createFromTimeString($workPeriod->start_at)->subHours($allowedHoursBefore)->format('H:i:s')
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
                    // dd($condition,$currentTimeObj,$earliestStart,$periodEndDateTime);
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


    protected function getPeriodsForDate($employeePeriods, string $date)
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('D')); // مثال: 'mon', 'tue'...

        return $employeePeriods->filter(function ($employeePeriod) use ($dayOfWeek, $date) {
            foreach ($employeePeriod->days as $dayRow) {
                // dd($employeePeriod,$employeePeriod->days,$dayOfWeek);

                // ✅ تحقق من تطابق اليوم مثل "sun", "mon", ...
                $isCorrectDay = $dayRow->day_of_week === $dayOfWeek;
                // dd($employeePeriod, $employeePeriod->start_date, $employeePeriod->end_date, $date);
                // ✅ تحقق من تطابق النطاق الزمني للتاريخ الحالي
                $isWithinDateRange =
                    Carbon::parse($employeePeriod->start_date)->lte($date) &&
                    (
                        is_null($employeePeriod->end_date) ||
                        Carbon::parse($employeePeriod->end_date)->gte($date)
                    );

                // ✅ نسمح بإرجاع الفترة فقط إذا كان اليوم والتاريخ مقبولين
                if ($isCorrectDay && $isWithinDateRange) {
                    return true;
                }
            }

            return false;
        });
    }
}
