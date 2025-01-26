<?php

namespace App\Filament\Pages;

use App\Forms\Components\KeyPadTest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Notifications\NotificationAttendance;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\BasePage;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;

class AttendanecEmployeeTest extends BasePage
// implements HasForms

{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.attendanec-employee';
    private $date = '';
    // private $date ;
    private $time = '';
    // private $time ;

    public bool $typeHidden = true;
    public string $type = '';
    public ?array $data = [];
    public function hasLogo(): bool
    {
        return true;
    }

    public static function alignFormActionsStart(): void
    {
        static::$formActionsAlignment = Alignment::Start;
    }

    public static function alignFormActionsCenter(): void
    {
        static::$formActionsAlignment = Alignment::Center;
    }

    public static function alignFormActionsEnd(): void
    {
        static::$formActionsAlignment = Alignment::End;
    }

    public string $rfid = ''; // Bind this to the input

    public function appendToDisplay(string $value)
    {
        // Only append if input length is acceptable, reducing unnecessary operations
        if (strlen($this->rfid) < 10) {
            $this->rfid .= $value; // Append the value to the RFID input
        }
    }

    public function clearDisplay()
    {
        $this->rfid = ''; // Clear the RFID input
    }

    public function form(Form $form): Form
    {

        app()->setLocale('en');
        return $form
            ->schema([

                DateTimePicker::make('date_time')
                    ->label('التاريخ والوقت')
                    // ->timezone('Asia/Kuala_Lumpur')
                    ->prefixIcon('heroicon-o-clock')
                    ->default(now())
                    ->prefixIconColor('success')
                    ->required()
                    ->seconds(false)
                    ->hidden(function () {
                        return isSuperAdmin() ? false : true;
                    }),
                KeyPadTest::make('rfid')->default($this->rfid),
                // KeyPadTest::make('rfid')->default($this->rfid),
                // TextInput::make('rfid')
                //     ->autocomplete(false)
                //     ->label('Employee RFID')
                //     ->prefixIcon('heroicon-m-identification')
                //     ->prefixIconColor('success')
                //     ->label('قم بإدخال رقم التحضير  الخاص بك واضغط على زر البصمة')
                //     ->required()
                //     ->placeholder('RFID')
                //     ->maxLength(255),
                ToggleButtons::make('type')
                    ->required()
                    ->hidden(function () {
                        if ($this->typeHidden) {
                            return true;
                        }
                        return false;
                    })
                    ->live()
                    ->reactive()
                    ->options([
                        Attendance::CHECKTYPE_CHECKIN => Attendance::CHECKTYPE_CHECKIN_LABLE,
                        Attendance::CHECKTYPE_CHECKOUT => Attendance::CHECKTYPE_CHECKOUT_LABLE,
                    ])->inline()
                    ->icons(
                        [
                            Attendance::CHECKTYPE_CHECKIN => 'heroicon-o-banknotes',
                            Attendance::CHECKTYPE_CHECKOUT => 'heroicon-o-clock',
                        ]
                    )
                    ->colors([
                        Attendance::CHECKTYPE_CHECKIN => 'info',
                        Attendance::CHECKTYPE_CHECKOUT => Color::Red,
                    ]),
            ])->statePath('data');
    }

    public function submit()
    {
        // return redirect(request()->header('Referer'));
        // Only handle submission if input is valid
        $formData = $this->form->getState();

        // $rfid = $formData['rfid'];
        $rfid = $this->rfid;
        $formData['rfid'] = $rfid;


        if (is_null($rfid)) {
            return showWarningNotifiMessage('RFID cannot be null');
        }
        if (!$this->typeHidden && $formData['type'] != '') {
            $this->type = $formData['type'];
        }
        $this->clearDisplay();
        $handle = $this->handleEmployeePeriodData($formData);
        if (isset($handle['success']) && !$handle['success']) {
            return $this->sendWarningNotification($handle['message']);
        }
    }

    public function handleEmployeePeriodData($data)
    {
        $dateTime = now();

        if (isSuperAdmin()) {
            $dateTime = $data['date_time'];
        }

        // Create a Carbon instance
        $carbonDateTime = Carbon::parse($dateTime);

        // Get the date and time separately
        $date = $carbonDateTime->toDateString(); // Output: 2024-10-01
        $time = $carbonDateTime->toTimeString();

        $rfid = $data['rfid'];
        $empId = Employee::where('rfid', $rfid)->first()?->id;
        // dd($rfid,$empId);
        // dd($date,$time);
        $this->handleCreationAttendance($empId, $date, $time);
    }

    public function handleCreationAttendance($empId, $date, $time)
    {

        $employee = Employee::find($empId);
        if (!$employee) {
            return showWarningNotifiMessage('No Employee');
            exit;
        }
        $employeePeriods = $employee?->periods;
        if (!is_null($employee) && count($employeePeriods) > 0) {
            $day = \Carbon\Carbon::parse($date)->format('l');

            // Decode the days array for each period
            $workTimePeriods = $employee->periods->map(function ($period) {
                $period->days = json_decode($period->days); // Ensure days are decoded
                return $period;
            });

            // Filter periods by the day
            $periodsForDay = $workTimePeriods->filter(function ($period) use ($day) {
                return in_array($day, $period->days);
            });

            // Check if no periods are found for the given day
            if ($periodsForDay->isEmpty()) {
                return
                    [
                        'success' => false,
                        'message' => __('notifications.you_dont_have_periods_today') . ' (' . $day . ')',
                    ];
            }

            // dd($time,$day,$date);
            $this->handleAttendance($employee, $time, $date, $day, $periodsForDay);
        } elseif (!is_null($employee) && count($employeePeriods) == 0) {

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
    public function findClosestPeriod($time, $periods)
    {
        // تحويل $time إلى كائن وقت
        $current_time = strtotime($time);

        // متغير لحفظ أقل فرق زمني والفترة الأقرب
        $min_difference = null;
        $closest_period = null;

        foreach ($periods as $period) {
            // تحويل وقتي البداية والنهاية إلى كائنات وقت
            $period_start = strtotime($period['start_at']);
            $period_end = strtotime($period['end_at']);

            // حساب الفرق بين الوقت الحالي ووقت البداية
            $difference_from_start = abs($current_time - $period_start);
            // حساب الفرق بين الوقت الحالي ووقت النهاية
            $difference_from_end = abs($current_time - $period_end);

            // استخدام الفرق الأصغر بين البداية والنهاية
            $closest_difference = min($difference_from_start, $difference_from_end);

            // تحديث الفترة الأقرب إذا كان الفرق أصغر من الفرق السابق
            if (is_null($min_difference) || $closest_difference < $min_difference) {
                $min_difference = $closest_difference;
                $closest_period = $period;
            }
        }
        return $closest_period;
    }

    public function handleAttendance($employee, $time, $date, $day, $periodsForDay)
    {
        $closestPeriod = $this->findClosestPeriod($time, $periodsForDay);

        if (!$closestPeriod) {
            $message = __('notifications.no_valid_period_found_for_the_specified_time') . $time;
            // No period found, so we return with an error or handle accordingly
            Attendance::storeNotAccepted($employee, $date, $time, $day, $message, $closestPeriod->id);
            return $this->sendWarningNotification($message);
        }

        // dd($this->checkTimeIfOutOfAllowedAttedance($closestPeriod,$time));
        if ($this->checkTimeIfOutOfAllowedAttedance($closestPeriod, $time)) {
            $message = 'You cannot check in right now.<br> Please contact your manager to adjust your shift.';

            Attendance::storeNotAccepted($employee, $date, $time, $day, $message, $closestPeriod->id);
            return $this->sendWarningNotification($message);
        }


        $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $closestPeriod->start_at)->subHours((int) Setting::getSetting('hours_count_after_period_before'))->format('H:i:s');

        if ($time >= $allowedTimeBeforePeriod && $time <= '23:59:00' && $closestPeriod?->start_at == '00:00:00') {
            $date = Carbon::parse($date)->addDay()->toDateString();
            $day = Carbon::parse($date)->format('l');
        }


        $existAttendance = $this->getExistingAttendance($employee, $closestPeriod, $date, $day, $time);

        if (isset($existAttendance['in_previous'])) {
            if ($existAttendance['in_previous']['check_type'] == Attendance::CHECKTYPE_CHECKIN) {

                // dd($date);
                return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKOUT, $existAttendance);
            } else {

                $endTime = \Carbon\Carbon::parse($closestPeriod->end_at);
                $checkTime = \Carbon\Carbon::parse($time);

                if ($endTime->gt($checkTime)) {

                    return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKIN, $existAttendance);
                } else {
                    $message = __('notifications.attendance_time_is_greater_than_current_period_end_time') . ':(' . $closestPeriod?->name . ')';
                    Attendance::storeNotAccepted($employee, $date, $time, $day, $message, $closestPeriod->id);
                    return $this->sendWarningNotification($message);
                }
            }
        }

        // Determine the action based on attendance count
        $attendanceCount = $existAttendance->count();

        if ($attendanceCount === 0) {

            $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $closestPeriod->start_at)->subHours((int) Setting::getSetting('hours_count_after_period_before'))->format('H:i:s');

            if ($closestPeriod->start_at == '00:00:00' && $time >= $allowedTimeBeforePeriod && $time <= '23:59:00') {
                $diff = false;
            } else {

                $diff = $this->calculateTimeDifferenceV2($time, $date, $closestPeriod);
            }
            //    get difference between current time (checktime) & the end of period


            // dd($diff, Setting::getSetting('hours_count_after_period_after'), $time, $closestPeriod->end_at);
            if ($diff) {
                if ($this->typeHidden) {
                    $this->typeHidden = false;
                    $message = 'please specify type ';
                    Attendance::storeNotAccepted($employee, $date, $time, $day, $message, $closestPeriod->id);
                    return $this->sendWarningNotification($message);
                } else if (!$this->typeHidden && $this->type != '') {
                    $this->typeHidden = true;
                    return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, $this->type);
                } else {
                    $message = 'please specify type also';
                    Attendance::storeNotAccepted($employee, $date, $time, $day, $message, $closestPeriod->id);
                    return $this->sendWarningNotification($message);
                }
            }

            $checkType = Attendance::CHECKTYPE_CHECKIN;
        } elseif ($attendanceCount > 0) {
            // تحقق مما إذا كان العدد زوجي أو فردي
            if ($attendanceCount % 2 === 0) {
                $checkType = Attendance::CHECKTYPE_CHECKIN;
            } else {
                $checkType = Attendance::CHECKTYPE_CHECKOUT;
            }
        }
        // dd($checkType);

        return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, $checkType);
    }

    public function createAttendance($employee, $nearestPeriod, $date, $checkTime, $day, $checkType, $previousRecord = null, $isRequest = false)
    {
        $checkTimeStr = $checkTime;
        // Ensure that $checkTime is a Carbon instance
        // $checkTime = \Carbon\Carbon::parse($checkTime);
        $checkTime = Carbon::parse($date . ' ' . $checkTime);

        $lastRecord = Attendance::where('created_at', '>=', Carbon::now()->subMinutes(15))->where('accepted', 1)->where('employee_id', $employee->id)->first();

        // if ($lastRecord && !$isRequest) {
        //     // // Calculate the remaining seconds until a new record can be created
        //     $remainingSeconds = Carbon::parse($lastRecord->created_at)->addMinutes(15)->diffInSeconds(Carbon::now());

        //     // Convert seconds to minutes and seconds
        //     $remainingMinutes = floor($remainingSeconds / 60);
        //     $remainingSeconds = $remainingSeconds % 60;
        //     $remainingMinutes *= -1;
        //     $remainingSeconds *= -1;
        //     $message = __('notifications.please_wait_for_a') . ' ' . $remainingMinutes . ' ' . __('notifications.minutue') . ' ' . $remainingSeconds . ' ' . __('notifications.second');
        //     Attendance::storeNotAccepted($employee, $date, $checkTimeStr, $day, $message, $nearestPeriod->id);
        //     return $this->sendWarningNotification($message);
        // }

        // dd($checkTime,$date);
        // Prepare attendance data
        $attendanceData = [
            'employee_id' => $employee->id,
            'period_id' => $nearestPeriod->id,
            'check_date' => $date,
            'check_time' => $checkTime,
            'day' => $day,
            'check_type' => $checkType,
            'branch_id' => $employee?->branch?->id,
            'created_by' => 0, // Consider changing this to use the authenticated user ID if applicable
        ];

        // Handle check-in and check-out scenarios
        if ($checkType === Attendance::CHECKTYPE_CHECKIN) {

            $periodEndTime = $nearestPeriod->end_at;
            $periodStartTime = $nearestPeriod->start_at;

            // $diff = $this->calculateTimeDifference($checkTime->toTimeString(), $periodStartTime,$date);
            $diff = $this->calculateTimeDifferenceV3($checkTime->toTimeString(), $nearestPeriod, $date);

            // dd($this->checkIfPeriodAllowenceMinutesBeforePeriod($nearestPeriod,$date,$checkTimeStr));
            // dd($nearestPeriod?->start_at,$nearestPeriod?->end_at);
            if (
                !$this->checkIfPeriodAllowenceMinutesBeforePeriod($nearestPeriod, $date, $checkTimeStr) && ($nearestPeriod?->start_at != '00:00:00')
            ) {
                $message = 'You cannot check in right now.<br> Please contact your manager to adjust your shift.';
                Attendance::storeNotAccepted($employee, $date, $checkTimeStr, $day, $message, $nearestPeriod->id);

                return $this->sendWarningNotification($message);
            }

            // dd($diff);

            if (
                $checkTime->toTimeString() < $periodStartTime &&
                $diff > Setting::getSetting('hours_count_after_period_after') && $this->type == ''
            ) {
                $message = __('notifications.you_cannot_attendance_before') . ' ' . $diff . ' ' . __('notifications.hours');
                Attendance::storeNotAccepted($employee, $date, $checkTimeStr, $day, $message, $nearestPeriod->id);
                return $this->sendWarningNotification($message);
            }

            if (
                $periodStartTime > $periodEndTime
                && ($checkTimeStr >= '00:00:00' && $checkTimeStr < $periodEndTime) && $previousRecord == null

            ) {
                $minusDate = strtotime("$date -1 day");
                $prevDayName = date('l', $minusDate);
                $prevDate = date('Y-m-d', $minusDate);
                $attendanceData['check_date'] = $prevDate;
                $attendanceData['day'] = $prevDayName;
            }

            if ($periodStartTime);
            if ($previousRecord) {
                $attendanceData['is_from_previous_day'] = 1;
                $attendanceData['check_date'] = $previousRecord['in_previous']?->check_date;
            }

            $attendanceData = array_merge($attendanceData, $this->storeCheckIn($nearestPeriod, $checkTime, $date));
            //   dd($attendanceData);
            $notificationMessage = __('notifications.the_attendance_has_been_recorded');
        } elseif ($checkType === Attendance::CHECKTYPE_CHECKOUT) {

            $periodEndTime = $nearestPeriod->end_at;

            if (
                $checkTime->toTimeString() > $periodEndTime
                &&
                ($periodEndTime > $nearestPeriod->start_at && $periodEndTime != '12:00:00')
            ) {

                $diff = $this->calculateTimeDifference($periodEndTime, $checkTime->toTimeString(), $date);
                //  dd($diff);
                if ($diff >= Setting::getSetting('hours_count_after_period_after')) {
                    $message = 'Times Up';
                    Attendance::storeNotAccepted($employee, $date, $checkTime->toTimeString(), $day, $message, $nearestPeriod->id);
                    return $this->sendWarningNotification($message);
                }
            }
            $attendanceData = array_merge($attendanceData, $this->storeCheckOut($nearestPeriod, $employee->id, $date, $checkTime, $previousRecord));
            $notificationMessage = __('notifications.the_departure_has_been_recorded');
        }

        // Try to create the attendance record
        try {
            Attendance::create($attendanceData);
            // Send success notification
            return $this->sendAttendanceNotification($employee->name, $notificationMessage);
        } catch (\Exception $e) {
            Attendance::storeNotAccepted($employee, $date, $checkTime->toTimeString(), $day, $e->getMessage(), $nearestPeriod->id);
            // Send warning notification in case of failure
            return $this->sendWarningNotification($e->getMessage());
        }
    }

    /**
     * get existing attendance
     */
    private function getExistingAttendance($employee, $closestPeriod, $date, $day, $currentCheckTime)
    {
        // dd($date,$day);
        $attendances = Attendance::where('employee_id', $employee->id)
            ->where('period_id', $closestPeriod->id) // Using array key if closestPeriod is an array
            ->where('check_date', $date)
            ->where('accepted', 1)
            ->where('day', $day)
            ->select('check_type', 'check_date')
            ->get();
        // dd($attendances,$attendances->count(),$date);
        if ($attendances->count() === 0) {

            $previousDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
            $previousDayName = \Carbon\Carbon::parse($date)->subDay()->format('l');
            $attendanceInPreviousDay = Attendance::where('employee_id', $employee->id)
                ->where('accepted', 1)
                ->where('period_id', $closestPeriod->id)
                ->where('check_date', $previousDate)
                ->latest('id')
                ->first();
            // dd($attendanceInPreviousDay);

            if ($attendanceInPreviousDay) {
                $isLatestSamePeriod = $this->checkIfSamePeriod($employee->id, $attendanceInPreviousDay, $closestPeriod, $previousDate, $date, $currentCheckTime);
                // dd($isLatestSamePeriod);
                if (!$isLatestSamePeriod) {
                    return $attendances;
                }

                if (($attendanceInPreviousDay->check_type == Attendance::CHECKTYPE_CHECKIN)) {
                    return [
                        'in_previous' => $attendanceInPreviousDay,
                        'previous_day_name' => $previousDayName,
                        'check_type' => Attendance::CHECKTYPE_CHECKOUT,
                    ];
                } else {
                    return [
                        'in_previous' => $attendanceInPreviousDay,
                        'previous_day_name' => $previousDayName,
                        'check_type' => Attendance::CHECKTYPE_CHECKIN,
                    ];
                }
            }

            return $attendances;
        }
        return $attendances;
    }

    /**
     * to create notification
     */
    private function createNotification($checkType, $employee) {}

    /**
     * to store checkin attendance
     */
    private function storeCheckIn($nearestPeriod, $checkTime, $date)
    {


        // dd($allowedTimeBeforePeriod,$checkTime?->toTimeString());
        $allowedLateMinutes = $nearestPeriod?->allowed_count_minutes_late;
        // $startTime = \Carbon\Carbon::parse($nearestPeriod->start_at);
        $startTime = \Carbon\Carbon::parse($date . ' ' . $nearestPeriod->start_at);

        // dd($nearestPeriod?->start_at,$nearestPeriod?->end_at,$checkTime?->toTimeString());
        // dd($nearestPeriod->start_at);
        if ($checkTime->lt($startTime) && $nearestPeriod?->start_at) {
            // Employee is early
            $data['delay_minutes'] = 0;
            $data['early_arrival_minutes'] = $checkTime->diffInMinutes($startTime);
            $data['status'] = $data['early_arrival_minutes'] >= Setting::getSetting('early_attendance_minutes') ?  Attendance::STATUS_EARLY_ARRIVAL : Attendance::STATUS_ON_TIME;
        } else {
            // Employee is late
            $data['delay_minutes'] = $startTime->diffInMinutes($checkTime);
            $data['early_arrival_minutes'] = 0;
            if ($allowedLateMinutes > 0) {
                $data['status'] = ($data['delay_minutes'] <= $allowedLateMinutes) ? Attendance::STATUS_ON_TIME : Attendance::STATUS_LATE_ARRIVAL;
            } else {
                $data['status'] = Attendance::STATUS_LATE_ARRIVAL;
            }
        }

        $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $nearestPeriod->start_at)->subHours((int) Setting::getSetting('hours_count_after_period_before'))->format('H:i:s');
        if ($checkTime->toTimeString() <= '23:59:00' && $nearestPeriod?->start_at == '00:00:00') {
            if ($checkTime->toTimeString() >= $allowedTimeBeforePeriod) {
                $data['status'] = Attendance::STATUS_EARLY_ARRIVAL;
            }
            $data['early_arrival_minutes'] = ($checkTime->subDay())->diffInMinutes($startTime);
        }
        return $data;
    }

    /**
     * to store checkout attendance
     */
    private function storeCheckOut($nearestPeriod, $employeeId, $date, $checkTime, $previousCheckInRecord = null)
    {
        $startTime = \Carbon\Carbon::parse($nearestPeriod->start_at);
        // $endTime = \Carbon\Carbon::parse($nearestPeriod->end_at);
        $endTime = Carbon::parse($date . ' ' . $nearestPeriod->end_at);

        // Find the corresponding check-in record
        $checkinRecord = Attendance::where('employee_id', $employeeId)
            ->where('period_id', $nearestPeriod->id)
            ->where('accepted', 1)
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->whereDate('check_date', $date) // Use the provided check date
            ->latest('id')
            ->first();

        if ($checkinRecord) {

            $checkinTime = \Carbon\Carbon::parse($checkinRecord->check_time);

            // Calculate the actual duration (from check-in to check-out)
            $actualDuration = $checkinTime->diff($checkTime);
            $hoursActual = $actualDuration->h;
            $minutesActual = $actualDuration->i;

            // Store both durations in a format like "hours:minutes"
            $currentDurationFormatted = sprintf('%02d:%02d', $hoursActual, $minutesActual);
            $data['actual_duration_hourly'] = $currentDurationFormatted;
            $data['supposed_duration_hourly'] = $nearestPeriod?->supposed_duration;
            $data['checkinrecord_id'] = $checkinRecord?->id;
            $data['check_date'] = $date;
            $hasCheckoutForDate = $this->checkHasCheckoutInDate($nearestPeriod, $employeeId, $date);
            if ($hasCheckoutForDate) {
                $totalDurationFormatted = $this->totalActualDurationHourly($date, $nearestPeriod, $employeeId);
                $totalDuration = Carbon::createFromFormat('H:i', $totalDurationFormatted);
                $currentDuration = Carbon::createFromFormat('H:i', $currentDurationFormatted);

                // Add the durations
                $sumDuration = $totalDuration->addMinutes($currentDuration->hour * 60 + $currentDuration->minute);

                // Format the result back to H:i format
                $sumDurationFormatted = $sumDuration->format('H:i');
            } else {
                $sumDurationFormatted = $currentDurationFormatted;
            }
        } else if (!$checkinRecord && $previousCheckInRecord) {

            $previousDayName = $previousCheckInRecord['previous_day_name'];
            $previousCheckInRecord = $previousCheckInRecord['in_previous'];

            // Combine the date and time into one string
            $dateTimeString = $previousCheckInRecord->check_date . ' ' . $previousCheckInRecord->check_time;

            $checkinTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString);

            // Calculate the actual duration (from check-in to check-out)
            $actualDuration = $checkinTime->diff($checkTime);
            $hoursActual = $actualDuration->h;
            $minutesActual = $actualDuration->i;

            $currentDurationFormatted = sprintf('%02d:%02d', $hoursActual, $minutesActual);
            // Store both durations in a format like "hours:minutes"
            $data['actual_duration_hourly'] = $currentDurationFormatted;
            $data['supposed_duration_hourly'] = $nearestPeriod?->supposed_duration;
            $data['checkinrecord_id'] = $previousCheckInRecord?->id;
            $data['check_date'] = $previousCheckInRecord?->check_date;
            $data['day'] = $previousDayName;
            $data['is_from_previous_day'] = 1;

            $hasCheckoutForDate = $this->checkHasCheckoutInDate($nearestPeriod, $employeeId, $previousCheckInRecord?->check_date);
            if ($hasCheckoutForDate) {
                $totalDurationFormatted = $this->totalActualDurationHourly($previousCheckInRecord?->check_date, $nearestPeriod, $employeeId);

                $totalDuration = Carbon::createFromFormat('H:i', $totalDurationFormatted);
                $currentDuration = Carbon::createFromFormat('H:i', $currentDurationFormatted);

                // Add the durations
                $sumDuration = $totalDuration->addMinutes($currentDuration->hour * 60 + $currentDuration->minute);

                // Format the result back to H:i format
                $sumDurationFormatted = $sumDuration->format('H:i');
            } else {

                $sumDurationFormatted = $currentDurationFormatted;
            }
        }
        // Calculate late departure or early departure
        if ($checkTime->gt($endTime)) {
            // Late departure
            $data['late_departure_minutes'] = $endTime->diffInMinutes($checkTime);
            $data['early_departure_minutes'] = 0;
            $data['status'] = Attendance::STATUS_LATE_DEPARTURE;
        } else if ($endTime->gt($checkTime)) {
            // Early departure
            $data['late_departure_minutes'] = 0;
            $data['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
            $data['status'] = Attendance::STATUS_EARLY_DEPARTURE;
        } else {
            $data['late_departure_minutes'] = 0;
            $data['early_departure_minutes'] = 0;
            $data['status'] = Attendance::STATUS_ON_TIME;
        }

        $data['delay_minutes'] = 0;
        $data['total_actual_duration_hourly'] = $sumDurationFormatted ?? 0;
        $allowedTimeAfterPeriod = Carbon::createFromFormat('H:i:s', $nearestPeriod->end_at)->addHours((int) Setting::getSetting('hours_count_after_period_after'))->format('H:i:s');
        if (
            $nearestPeriod->end_at > $allowedTimeAfterPeriod &&
            $checkTime->toTimeString() < $nearestPeriod->end_at &&
            $allowedTimeAfterPeriod > $checkTime->toTimeString()
        ) {

            $nearestPeriodEnd = Carbon::parse($nearestPeriod->end_at)->subDay();
            $data['status'] = Attendance::STATUS_LATE_DEPARTURE;
            $data['delay_minutes'] = $nearestPeriodEnd->diffInMinutes($checkTime);
            $data['early_arrival_minutes'] = 0;
            $data['early_departure_minutes'] = 0;
        }
        if ($nearestPeriod->day_and_night) {
            if ($checkTime->toTimeString() > $nearestPeriod->start_at && $checkTime->toTimeString() <= '23:59:59') {
                $endTime = $endTime->addDay();
                $data['late_departure_minutes'] = 0;
                $data['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
                $data['status'] = Attendance::STATUS_EARLY_DEPARTURE;
            }
        }
        return $data;
    }

    private function sendAttendanceNotification($employeeName, $message)
    {
        return NotificationAttendance::make()
            ->title(__('notifications.welcome_employee') . ' ' . $employeeName)
            ->body($message)
            ->icon('heroicon-o-check-circle')
            ->iconSize(IconSize::Large)
            ->iconPosition(IconPosition::Before)
            ->duration(10000)
            ->iconColor('success')
            ->success()
            ->send();
    }

    private function sendWarningNotification($message)
    {

        return NotificationAttendance::make()
            ->title(__('notifications.notify'))
            ->body($message)
            ->icon('heroicon-o-exclamation-triangle')
            ->iconSize(IconSize::Large)
            ->iconPosition(IconPosition::Before)
            ->duration(10000)
            ->iconColor('warning')
            ->warning()
            ->send();
    }

    /**
     * to get total actual duration hourly
     */
    private function totalActualDurationHourly($date, $nearestPeriod, $employeeId)
    {

        $previousActualDurationHours = Attendance::where('check_date', $date)
            ->where('accepted', 1)
            ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
            ->where('period_id', $nearestPeriod->id)
            ->where('employee_id', $employeeId)
            ->select('actual_duration_hourly')
            ->get()
            ->pluck('actual_duration_hourly')
            ->toArray();

        $totalMinutes = 0; // Initialize total minutes

        // Loop through the duration times and convert each to minutes
        foreach ($previousActualDurationHours as $duration) {
            $time = Carbon::parse($duration);
            $totalMinutes += ($time->hour * 60) + $time->minute; // Convert hours to minutes and add minutes
        }

        $totalHours = intdiv($totalMinutes, 60); // Get the whole number of hours
        $remainingMinutes = $totalMinutes % 60; // Get the remaining minutes

        $totalDurationFormatted = sprintf('%02d:%02d', $totalHours, $remainingMinutes);
        return $totalDurationFormatted;
    }

    /** to check if has chechout for date */
    private function checkHasCheckoutInDate($nearestPeriod, $employeeId, $date)
    {
        return Attendance::where('employee_id', $employeeId)
            ->where('accepted', 1)
            ->where('period_id', $nearestPeriod->id)
            ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
            ->whereDate('check_date', $date)->exists();
    }

    /**
     * check if attendanceInPreviousDay is completed
     */
    private function checkIfattendanceInPreviousDayIsCompleted($attendanceInPreviousDay, $period, $currentCheckTime, $currentDate, $currentRealDate)
    {
        $previousDate = $attendanceInPreviousDay?->check_date;
        $periodId = $attendanceInPreviousDay?->period_id;
        $employeId = $attendanceInPreviousDay?->employee_id;
        $periodEndTime = $period->end_at;
        $periodStartTime = $period->start_at;

        $allowedTimeAfterPeriod = Carbon::createFromFormat('H:i:s', $periodEndTime)->addHours((int) Setting::getSetting('hours_count_after_period_after'))->format('H:i:s');

        $latstAttendance = Attendance::where('employee_id', $employeId)
            ->where('accepted', 1)
            ->where('period_id', $periodId)
            ->where('check_date', $previousDate)
            ->select('id', 'check_type', 'check_date', 'check_time', 'is_from_previous_day')
            ->latest('id')
            ->first();

        $lastCheckType = $latstAttendance->check_type;

        $dateTimeString = $attendanceInPreviousDay->check_date . ' ' . $latstAttendance->check_time;
        $lastCheckTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString);

        $dateTimeString = $currentRealDate . ' ' . $currentCheckTime;
        $currentDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString);

        $diff = $this->calculateTimeDifference($periodEndTime, $currentCheckTime, $currentRealDate);

        $lastCheckedPeriodEndTimeDateTime = Carbon::parse($attendanceInPreviousDay->check_date . ' ' . $allowedTimeAfterPeriod);

        if ($currentCheckTime > $periodEndTime) {
            if ($diff >= Setting::getSetting('hours_count_after_period_after')) {
                return true;
            }
        }
        if ($period->day_and_night) {

            if ($lastCheckType == Attendance::CHECKTYPE_CHECKOUT) {
                if ($currentCheckTime >= $periodEndTime) {
                    return true;
                }
            } else {
                if ($currentCheckTime >= $periodStartTime) {
                    return true;
                }
            }
        } else {
            if ($currentDateTime->toTimeString() > $lastCheckedPeriodEndTimeDateTime->toTimeString()) {
                return true;
            }
            if ($currentCheckTime < $periodEndTime && $currentCheckTime > $allowedTimeAfterPeriod) {
                $diff = $this->calculateTimeDifference($periodEndTime, $currentCheckTime, $currentRealDate);

                if ($diff >= Setting::getSetting('hours_count_after_period_after')) {
                    return true;
                }
            }
            if ($lastCheckType == Attendance::CHECKTYPE_CHECKOUT) {
                if ($lastCheckTime >= $periodEndTime) {
                    return true;
                }
            } else {
                if ($currentCheckTime >= $periodStartTime) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkIfSamePeriod($employeeId, $attendanceInPreviousDay, $period, $date, $currentDate, $checkTime)
    {
        // dd($employeeId, $attendanceInPreviousDay, $period, $date, $currentDate, $checkTime);
        $latstAttendance = Attendance::where('employee_id', $employeeId)
            ->where('accepted', 1)
            ->select('id', 'check_type', 'check_date', 'check_time', 'period_id')
            ->latest('id')
            ->first();
        // dd($latstAttendance&& $latstAttendance->period_id == $period->id);
        if ($latstAttendance && $latstAttendance->period_id == $period->id) {
            $isPreviousCompleted = $this->checkIfattendanceInPreviousDayIsCompleted($attendanceInPreviousDay, $period, $checkTime, $date, $currentDate);
            //   dd($isPreviousCompleted);
            if (!$isPreviousCompleted) {
                return true;
            }
        }
        return false;
    }

    public function calculateTimeDifference(string $currentTime, string $endTime, $date = null): float
    {
        // Create DateTime objects for each time
        $currentDateTime = Carbon::parse($date . ' ' . $currentTime);
        $periodEndDateTime = Carbon::parse($date . ' ' . $endTime);

        // Calculate the difference
        $diff = $currentDateTime->diff($periodEndDateTime);

        // Get the total difference in hours
        $totalHours = $diff->h + ($diff->i / 60); // Include minutes as a fraction of an hour
        // If the current time is greater than the end time, calculate total hours accordingly
        if ($currentDateTime > $periodEndDateTime) {
            // Circular manner (i.e., next day)
            $totalHours = (24 - $periodEndDateTime->format('H')) + $currentDateTime->format('H') + (($currentDateTime->format('i') - $periodEndDateTime->format('i')) / 60);
        }
        $res = round($totalHours, 2);
        // dd($res);        
        return  $res;
    }
    public function calculateTimeDifferenceV3(string $currentTime, $period, $date)
    {
        $endTime = $period?->end_at;
        $startTime = $period?->start_at;
        // Create DateTime objects for each time
        $currentDateTime = Carbon::parse($date . ' ' . $currentTime);
        // dd($endTime);
        if ($period->day_and_night && ($currentTime >= '00:00:00' && $currentTime <= $endTime)) {
            $date = Carbon::createFromFormat('Y-m-d', $date)->subDay()->format('Y-m-d');
        }

        $periodEndDateTime = Carbon::parse($date . ' ' . $startTime);
        // Calculate the difference
        $diff = $currentDateTime->diff($periodEndDateTime);
        // Get the total difference in hours
        $totalHours = $diff->h + ($diff->i / 60); // Include minutes as a fraction of an hour

        // If the current time is greater than the end time, calculate total hours accordingly
        if ($currentDateTime > $periodEndDateTime) {
            // Circular manner (i.e., next day)
            $totalHours = (24 - $periodEndDateTime->format('H')) + $currentDateTime->format('H') + (($currentDateTime->format('i') - $periodEndDateTime->format('i')) / 60);
        }
        $res = round($totalHours, 2);

        return  $res;
    }

    public function checkIfPeriodAllowenceMinutesAfterPeriod($period, $date, $time)
    {
        $bool = false;

        $allowedTimeAfterPeriod = Carbon::createFromFormat('H:i:s', $period->end_at)->addHours((int) Setting::getSetting('hours_count_after_period_after'))->format('H:i:s');


        $currentDateTime = Carbon::parse($date . ' ' . $time);
        // dd($allowedTimeAfterPeriod,$period?->end_at);
        $periodEndTimeDateTime = Carbon::parse($date . ' ' . $allowedTimeAfterPeriod);
        if ($period->day_and_night || ($allowedTimeAfterPeriod < $period?->end_at)) {
            $periodEndTimeDateTime = $periodEndTimeDateTime->addDay();
        }

        if ($currentDateTime->gt($periodEndTimeDateTime)) {
            $bool = true;
        }
        // dd($bool,( $allowedTimeAfterPeriod < $period?->end_at), $allowedTimeAfterPeriod , $period?->end_at,$currentDateTime,$periodEndTimeDateTime);
        return $bool;
    }

    public function checkIfPeriodAllowenceMinutesBeforePeriod($period, $date, $time)
    {


        $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $period->start_at)->subHours((int) Setting::getSetting('hours_count_after_period_before'))->format('H:i:s');

        $periodStartTimeDateTime = Carbon::parse($date . ' ' . $allowedTimeBeforePeriod);
        $currentDateTime = Carbon::parse($date . ' ' . $time);
        // dd($currentDateTime,$periodStartTimeDateTime);
        if ($period->day_and_night) {
            $periodStartTimeDateTime = $periodStartTimeDateTime->subDay();
        }

        // dd($currentDateTime,$periodStartTimeDateTime);
        if ($currentDateTime->gt($periodStartTimeDateTime)) {
            return true;
        }
        return false;
    }
    public function calculateTimeDifferenceV2(string $currentTime, string $date, $period): bool
    {
        $periodEndTime = $period->end_at; // Expected to be just "HH:MM:SS"
        $isDayAndNight = $period->day_and_night;
        // Create DateTime objects by combining $date with the period's start and end times
        $currentDateTime = Carbon::parse($date . ' ' . $currentTime);

        $periodEndDateTime = Carbon::parse($date . ' ' . $periodEndTime);
        if ($isDayAndNight) {
            $periodEndDateTime = Carbon::parse($date . ' ' . $periodEndTime)->addDay();
        }

        $diffWithEndPeriod = $currentDateTime->diffInHours($periodEndDateTime);
        // dd($currentDateTime,$periodEndDateTime,$currentDateTime->diffInHours($periodEndDateTime));
        // dd($diffWithEndPeriod,$isDayAndNight);
        if ($diffWithEndPeriod <= Setting::getSetting('pre_end_hours_for_check_in_out')) {
            return true;
        }
        return false;
    }

    public function checkTimeIfOutOfAllowedAttedance($period, $time)
    {
        $allowedTimeAfterPeriod = Carbon::createFromFormat('H:i:s', $period->end_at)
            ->addHours((int) Setting::getSetting('hours_count_after_period_after'))
            ->format('H:i:s');

        $allowedTimeBeforePeriod = Carbon::createFromFormat('H:i:s', $period->start_at)
            ->subHours((int) Setting::getSetting('hours_count_after_period_before'))
            ->format('H:i:s');
        // dd($time,$allowedTimeBeforePeriod,$allowedTimeAfterPeriod);
        // Check if the time is within the range
        if ($time >= $allowedTimeAfterPeriod && $time <= $allowedTimeBeforePeriod) {
            return true; // $time is within the allowed range
        }

        return false; // $time is outside the allowed range
    }
}
