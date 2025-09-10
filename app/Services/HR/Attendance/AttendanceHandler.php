<?php

namespace App\Services\HR\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\HR\MonthClosure\MonthClosureService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

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
    public $attendanceType = '';
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
        array $data,
        $attendanceType,
    ): array {
        $this->attendanceType = $attendanceType;
        try {
            $dateTime = $data['date_time']; // مفترض "Y-m-d H:i:s"
            $dt = CarbonImmutable::parse($dateTime);

            $this->realAttendanceDate = $dt->toDateString();
            $this->date        = $dt->toDateString();
            $this->nextDate    = $dt->addDay()->toDateString();
            $this->previousDate = $dt->subDay()->toDateString();
            $time              = $dt->format('H:i:s');
            $date              = $this->date;
            $this->employeeId         = $employee->id;


            // app(MonthClosureService::class)->ensureMonthIsOpen($date);

            if ($employee) {

                // $latstCheckIn = Attendance::where('employee_id', $this->employeeId)->with('period')
                //     ->where('accepted', 1)
                //     ->select('id', 'check_type', 'period_id', 'check_date', 'check_time')
                //     ->latest('id')->where('check_date', '>=', $this->previousDate)
                //     ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                //     ->first();

                $openCheckIn = Attendance::where('employee_id', $this->employeeId)
                    ->where('accepted', 1)
                    ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                    ->whereBetween('check_date', [$this->previousDate, $this->nextDate])
                    ->select('id', 'check_type', 'period_id', 'check_date', 'check_time')
                    ->whereDoesntHave('checkout', function ($q) {
                        // نفترض عندك علاقة اسمها checkout في الموديل
                        // اللي تجيب سجلات الخروج المرتبطة عبر checkinrecord_id
                        $q->where('check_type', Attendance::CHECKTYPE_CHECKOUT);
                    })
                    ->orderBy('check_date', 'asc') // ناخذ الأقدم
                    ->first();

                    $latstCheckIn = $openCheckIn;

                // dd($latstCheckIn,$openCheckIn);
                if ($latstCheckIn) {
                    $isClosed =    Attendance::isCheckinClosed(
                        $this->employeeId,
                        $latstCheckIn->period_id,
                        $latstCheckIn->check_date,
                        $date,
                        $time,
                        $latstCheckIn->id
                    );
                    if (!$isClosed) {
                        $this->workPeriod = $latstCheckIn->period;
                        if ($this->workPeriod) {
                            $this->hasWorkPeriod = true;
                            $this->workPeriod = $latstCheckIn->period;
                            $currentTimeObj = Carbon::createFromFormat('Y-m-d H:i:s', "{$this->date} {$time}");

                            // ✅ استدعاء الميثود
                            $this->attachBoundsToWorkPeriod($this->workPeriod, $currentTimeObj);
                            $this->day = strtolower(Carbon::parse($latstCheckIn->check_date)->format('D'));
                            $this->date = $latstCheckIn->check_date;
                        }
                    }
                }
            }
            // dd($this->hasWorkPeriod,$this->workPeriod);
            $employeePeriods = $employee?->periods;
            // dd($employeePeriods);
            if (! is_null($employee) && count($employeePeriods) > 0) {
                $day = strtolower(Carbon::parse($date)->format('D'));

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
                        $day     = strtolower(Carbon::parse($targetDate)->format('D'));

                        foreach ($periods as $period) {
                            $wp = $period->workPeriod;
                            if (! $wp) continue;

                            // تحقّق فعلي من تفعيل الشفت في هذا التاريخ
                            $isActive = $this->periodHelper
                                ->hasWorkPeriodForDate($employee->id, $wp->id, $targetDate, $day);
                            if (! $isActive) continue;

                            // خزن الفترة مع تاريخها المرشح (لا تدفع تلقائي لشفتات 00:00)
                            $potentialPeriods->push([
                                'period'      => $period,
                                'candidateAt' => $targetDate,
                                'candidateDay' => $day,
                            ]);
                        }
                    }

                    $closestPeriod = $this->findClosestPeriodNew($time, $potentialPeriods);
                    $day = strtolower(Carbon::parse($this->date)->format('D'));
                } else {
                    $day = $this->day;
                    $closestPeriod = $this->workPeriod;
                }
                // dd($closestPeriod,$this->date,$this->targetDate);


                // dd(
                //     $closestPeriod?->id,
                //     $closestPeriod?->name,
                //     $this->date,
                //     $date,
                //     $day,
                //     'targetDate: ' . $this->targetDate,
                //     $closestPeriod ?  $this->periodHelper->hasWorkPeriodForDate($employee->id, $closestPeriod->id, $this->date, $day) : false
                // );

                if ($closestPeriod) {
                    $this->hasWorkPeriod = $this->periodHelper->hasWorkPeriodForDate($employee->id, $closestPeriod->id, $this->date, $day);
                }
                // Check if no periods are found for the given day 
                if (! $closestPeriod || ! $this->hasWorkPeriod) {

                    $message = __('notifications.you_dont_have_periods_today') . ' (' . $day . '-' . date('Y-m-d', strtotime($dateTime)) . ') ';
                    if ($closestPeriod) {
                        Attendance::storeNotAccepted($employee, $this->date, $time, $day, $message, $closestPeriod->id, Attendance::ATTENDANCE_TYPE_RFID);
                    }
                    return
                        [
                            'success' => false,
                            'message' => $message,
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

                // dd($date, $this->date,$closestPeriod?->name);
                $existAttendance = AttendanceFetcher::getExistingAttendance($employee, $closestPeriod, $this->date, $day, $time);
                // dd($existAttendance,$this->date);
                if (isset($existAttendance['in_previous'])) {
                    $this->date = $existAttendance['in_previous']->check_date;
                }

                // dd($existAttendance);

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


    protected function findClosestPeriodNew(string $time, $periods)
    {
        if (empty($periods) || count($periods) === 0) {
            return null;
        }

        // استخدم تاريخ الحضور الحقيقي (اللي جاء في الطلب) + الوقت
        $currentTimeObj = Carbon::createFromFormat('Y-m-d H:i:s', "$this->realAttendanceDate $time");

        $allowedAfter  = (int) \App\Models\Setting::getSetting('hours_count_after_period_after', 0);
        $allowedBefore = (int) \App\Models\Setting::getSetting('hours_count_after_period_before', 0);

        // dd($periods->pluck('period')->toArray());
        foreach ($periods as $period) {
            $wp = $period['period']->workPeriod;
            if (! $wp) {
                continue;
            }

            $res = $this->computeWorkPeriodBounds($currentTimeObj, $wp, $allowedBefore, $allowedAfter);
            // لو الوقت داخل نافذة السماح للفترة
            if ($res['inWindow']) {
                // ✅ فلترة إضافية: لازم يكون الوقت >= windowStart
                if ($currentTimeObj->lt($res['windowStart'])) {
                    continue; // خارج السماح (قبلها)
                }

                $this->date       = $res['periodStart']->toDateString();
                $this->targetDate = $this->date;
                // $this->targetDate = $res['windowStart']->toDateString();
                $this->targetDay  = strtolower($res['periodStart']->format('D'));

                // ✅ استدعاء الميثود مباشرة
                $this->attachBoundsToWorkPeriod($wp, $currentTimeObj);

                return $wp;
            }
        }

        // لا يوجد شفت مناسب ضمن نوافذ السماح
        return null;
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


    function computeWorkPeriodBounds(
        Carbon $currentTimeObj,
        object $workPeriod,
        int $allowedBefore = 0,
        int $allowedAfter = 0
    ): array {
        $startAt = (string) $workPeriod->start_at; // "H:i:s"
        $endAt   = (string) $workPeriod->end_at;   // "H:i:s"

        // تحديد إن كانت الفترة ليلية
        $hasFlag     = property_exists($workPeriod, 'day_and_night');
        $isOvernight = $hasFlag ? (bool) $workPeriod->day_and_night : ($endAt <= $startAt);

        // نقاط يوم الأساس (يوم currentTimeObj)
        $startToday = $currentTimeObj->copy()->setTimeFromTimeString($startAt);
        $endToday   = $currentTimeObj->copy()->setTimeFromTimeString($endAt);
        $timeOfDay  = $currentTimeObj->format('H:i:s');

        $origin = 'same-day';

        if (!$isOvernight) {
            // —— فترة نهارية —— (لا تعبر منتصف الليل)
            if ($timeOfDay >= $startAt && $timeOfDay <= $endAt) {
                // داخل فترة اليوم
                $periodStart = $startToday;
                $periodEnd   = $endToday;
                $origin      = 'same-day';
            } elseif ($timeOfDay < $startAt) {
                // قبل بداية اليوم ⇒ الفترة اليوم (قادمة بعد قليل)
                $periodStart = $startToday;
                $periodEnd   = $endToday;
                $origin      = 'same-day';
            } else { // $timeOfDay > $endAt
                // بعد نهاية اليوم ⇒ اختر الفترة القادمة (غدًا)
                $periodStart = $startToday->copy()->addDay();
                $periodEnd   = $endToday->copy()->addDay();
                $origin      = 'tomorrow';
            }
        } else {
            // —— فترة ليلية —— (تعبر منتصف الليل)
            if ($timeOfDay < $endAt) {
                // بعد منتصف الليل وحتى end_at ⇒ الفترة بدأت أمس وتنتهي اليوم
                $periodStart = $startToday->copy()->subDay();
                $periodEnd   = $endToday;
                $origin      = 'prev-day';
            } else {
                // باقي اليوم (سواء قبل start_at أو بعده) ⇒ الفترة تبدأ اليوم وتنتهي غدًا
                $periodStart = $startToday;
                $periodEnd   = $endToday->copy()->addDay();
                $origin      = 'same-day';
            }
        }

        // نافذة السماح
        $windowStart = $periodStart->copy()->subHours($allowedBefore);
        $windowEnd   = $periodEnd->copy()->addHours($allowedAfter);

        return [
            'periodStart' => $periodStart,
            'periodEnd'   => $periodEnd,
            'windowStart' => $windowStart,
            'windowEnd'   => $windowEnd,
            'isOvernight' => $isOvernight,
            'origin'      => $origin,
            'name'        => $workPeriod->name ?? null,
            'inWindow'    => $currentTimeObj->betweenIncluded($windowStart, $windowEnd),
        ];
    }

    protected function attachBoundsToWorkPeriod(object $workPeriod, Carbon $currentTimeObj): void
    {
        $allowedAfter  = (int) \App\Models\Setting::getSetting('hours_count_after_period_after', 0);
        $allowedBefore = (int) \App\Models\Setting::getSetting('hours_count_after_period_before', 0);

        $res = $this->computeWorkPeriodBounds($currentTimeObj, $workPeriod, $allowedBefore, $allowedAfter);

        // ثبّت التاريخ/اليوم
        $this->date       = $res['periodStart']->toDateString();
        $this->targetDate = $this->date;
        $this->day        = strtolower($res['periodStart']->format('D'));

        // ✅ علّق bounds كـ relation
        $workPeriod->setRelation('bounds', [
            'periodStart' => $res['periodStart'],
            'periodEnd'   => $res['periodEnd'],
            'windowStart' => $res['windowStart'],
            'windowEnd'   => $res['windowEnd'],
            'isOvernight' => $res['isOvernight'],
            'origin'      => $res['origin'],
            'name'        => $res['name'] ?? null,
            'currentTimeObj' => $currentTimeObj,
        ]);
    }
}
