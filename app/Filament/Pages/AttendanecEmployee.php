<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\BasePage;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;

class AttendanecEmployee extends BasePage
// implements HasForms

{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.attendanec-employee';
    private $date = '';
    // private $date ;
    private $time = '';
    // private $time ;

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

    public function form(Form $form): Form
    {

        app()->setLocale('ar');
        return $form
            ->schema([
                DateTimePicker::make('date_time')
                    ->label('التاريخ والوقت')
                // ->timezone('Asia/Kuala_Lumpur')
                    ->prefixIcon('heroicon-o-clock')
                    ->prefixIconColor('success')
                    ->required()->seconds(false),
                TextInput::make('rfid')
                    ->autocomplete(false)
                    ->label('Employee RFID')
                    ->prefixIcon('heroicon-m-identification')
                    ->prefixIconColor('success')
                    ->label('قم بإدخال رقم التحضير  الخاص بك واضغط على زر البصمة')
                    ->required()
                    ->placeholder('RFID')
                    ->maxLength(255),
            ])->statePath('data');
    }

    public function submit()
    {

        $formData = $this->form->getState();

        $handle = $this->handleEmployeePeriodData($formData);
        if (isset($handle['success']) && !$handle['success']) {
            return $this->sendWarningNotification($handle['message']);
        }
    }

    public function handleEmployeePeriodData($data)
    {

        $dateTime = $data['date_time'];

        // Create a Carbon instance
        $carbonDateTime = Carbon::parse($dateTime);

        // Get the date and time separately
        $date = $carbonDateTime->toDateString(); // Output: 2024-10-01
        $time = $carbonDateTime->toTimeString();

        $rfid = $data['rfid'];
        $employee = Employee::where('rfid', $rfid)->first();
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
                    'message' => 'لا يوجد لديك فترات فهذا اليوم  (' . $day . ')',
                ]
                ;
            }
            $this->handleAttendance($employee, $time, $date, $day, $periodsForDay);

        } elseif (!is_null($employee) && count($employeePeriods) == 0) {

            return
                [
                'success' => false,
                'message' => 'نأسف, لم يتم إضافة أي فترات دوام إليك, يرجى التواصل مع الإدارة!',
            ]
            ;
        } else {
            return
                [
                'success' => false,
                'message' => ' لا يوجد موظف بهذا الرقم  ' . $data['rfid'],
            ]
            ;

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
            // No period found, so we return with an error or handle accordingly
            return $this->sendWarningNotification('لم يتم العثور على فترة صالحة للوقت المحدد. ' . $time);
        }

        // Check if attendance exists for this period, date, and day
        $existAttendance = $this->getExistingAttendance($employee, $closestPeriod, $date, $day, $time);

        if (isset($existAttendance['in_previous'])) {
            if ($existAttendance['in_previous']['check_type'] == Attendance::CHECKTYPE_CHECKIN) {
                return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKOUT, $existAttendance);
            } else {

                $endTime = \Carbon\Carbon::parse($closestPeriod->end_at);
                $checkTime = \Carbon\Carbon::parse($time);

                if ($endTime->gt($checkTime)) {
                    // $date = $existAttendance['in_previous']?->check_date;
                    // $day = $existAttendance['previous_day_name'];

                    return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKIN, $existAttendance);
                } else {
                    return $this->sendWarningNotification('وقت الحضور أكبر من وقت نهاية الفترة الحالية :(' . $closestPeriod?->name . ')');

                }
            }
        }

        // Determine the action based on attendance count
        $attendanceCount = $existAttendance->count();
        if ($attendanceCount === 0) {
            $checkType = Attendance::CHECKTYPE_CHECKIN;
        } elseif ($attendanceCount > 0) {
            // تحقق مما إذا كان العدد زوجي أو فردي
            if ($attendanceCount % 2 === 0) {
                $checkType = Attendance::CHECKTYPE_CHECKIN;
            } else {
                $checkType = Attendance::CHECKTYPE_CHECKOUT;
            }
        }

        return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, $checkType);

    }

    private function createAttendance($employee, $nearestPeriod, $date, $checkTime, $day, $checkType, $previousRecord = null)
    {
        // Ensure that $checkTime is a Carbon instance
        $checkTime = \Carbon\Carbon::parse($checkTime);

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

            if ($previousRecord) {
                $attendanceData['is_from_previous_day'] = 1;
                $attendanceData['check_date'] = $previousRecord['in_previous']?->check_date;
            }

            $attendanceData = array_merge($attendanceData, $this->storeCheckIn($nearestPeriod, $checkTime));
            $notificationMessage = 'لقد تم تسجيل الحضور';
        } elseif ($checkType === Attendance::CHECKTYPE_CHECKOUT) {
            $attendanceData = array_merge($attendanceData, $this->storeCheckOut($nearestPeriod, $employee->id, $date, $checkTime, $previousRecord));
            $notificationMessage = 'لقد تم تسجيل الانصراف';
        }

        // Try to create the attendance record
        try {
            Attendance::create($attendanceData);
            // Send success notification
            return $this->sendAttendanceNotification($employee->name, $notificationMessage);
        } catch (\Exception $e) {
            // Send warning notification in case of failure
            return $this->sendWarningNotification($employee->name, $e->getMessage());
        }
    }
    /**
     * get existing attendance
     */
    private function getExistingAttendance($employee, $closestPeriod, $date, $day, $currentCheckTime)
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->where('period_id', $closestPeriod->id) // Using array key if closestPeriod is an array
            ->where('check_date', $date)
            ->where('day', $day)
            ->select('check_type', 'check_date')
            ->get();
        if ($attendances->count() === 0) {

            $previousDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
            $previousDayName = \Carbon\Carbon::parse($date)->subDay()->format('l');
            $attendanceInPreviousDay = Attendance::where('employee_id', $employee->id)
                ->where('period_id', $closestPeriod->id)
                ->where('check_date', $previousDate)
            // ->select('id', 'check_type', 'check_date')
            // ->doesntHave('checkoutRecord')
            // ->where('check_type','<',$closestPeriod->end_at)
            // ->where('check_type',Attendance::CHECKTYPE_CHECKIN)
                ->latest('id')
                ->first();

            if ($attendanceInPreviousDay) {
                $isLatestSamePeriod = $this->checkIfSamePeriod($employee->id, $attendanceInPreviousDay, $closestPeriod, $previousDate, $currentCheckTime);

                if (!$isLatestSamePeriod) {
                    return $attendances;
                }

                if (($attendanceInPreviousDay->check_type == Attendance::CHECKTYPE_CHECKIN)) {
                    return ['in_previous' => $attendanceInPreviousDay,
                        'previous_day_name' => $previousDayName,
                        'check_type' => Attendance::CHECKTYPE_CHECKOUT,
                    ];
                } else {
                    return ['in_previous' => $attendanceInPreviousDay,
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
    private function createNotification($checkType, $employee)
    {

    }

    /**
     * to store checkin attendance
     */
    private function storeCheckIn($nearestPeriod, $checkTime)
    {
        $allowedLateMinutes = $nearestPeriod?->allowed_count_minutes_late;
        $startTime = \Carbon\Carbon::parse($nearestPeriod->start_at);

        if ($checkTime->gt($startTime)) {
            // Employee is late
            $data['delay_minutes'] = $startTime->diffInMinutes($checkTime);
            $data['early_arrival_minutes'] = 0;
            if ($allowedLateMinutes > 0) {
                $data['status'] = ($data['delay_minutes'] <= $allowedLateMinutes) ? Attendance::STATUS_ON_TIME : Attendance::STATUS_LATE_ARRIVAL;
            } else {
                $data['status'] = Attendance::STATUS_LATE_ARRIVAL;
            }
        } else {
            // Employee is early
            $data['delay_minutes'] = 0;
            $data['early_arrival_minutes'] = $checkTime->diffInMinutes($startTime);
            $data['status'] = ($data['early_arrival_minutes'] == 0) ? Attendance::STATUS_ON_TIME : Attendance::STATUS_EARLY_ARRIVAL;

        }

        return $data;
    }

    /**
     * to store checkout attendance
     */
    private function storeCheckOut($nearestPeriod, $employeeId, $date, $checkTime, $previousCheckInRecord = null)
    {

        $startTime = \Carbon\Carbon::parse($nearestPeriod->start_at);
        $endTime = \Carbon\Carbon::parse($nearestPeriod->end_at);
        // Find the corresponding check-in record
        $checkinRecord = Attendance::where('employee_id', $employeeId)
            ->where('period_id', $nearestPeriod->id)
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
        $data['delay_minutes'] = 0; // Initialize for check-out
        $data['total_actual_duration_hourly'] = $sumDurationFormatted;
        return $data;
    }

    private function sendAttendanceNotification($employeeName, $message)
    {
        return Notification::make()
            ->title('مرحبا موظفنا العزيز ' . $employeeName)
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
        return Notification::make()
            ->title('تنبيه')
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
            ->where('period_id', $nearestPeriod->id)
            ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
            ->whereDate('check_date', $date)->exists();

    }

    /**
     * check if attendanceInPreviousDay is completed
     */
    private function checkIfattendanceInPreviousDayIsCompleted($attendanceInPreviousDay, $period, $currentCheckTime, $currentDate)
    {

        $date = $attendanceInPreviousDay?->check_date;
        $periodId = $attendanceInPreviousDay?->period_id;
        $employeId = $attendanceInPreviousDay?->employee_id;
        $periodEndTime = $period?->end_at;

        $latstAttendance = Attendance::where('employee_id', $employeId)
            ->where('period_id', $periodId)
            ->where('check_date', $date)
            ->select('id', 'check_type', 'check_date', 'check_time')
        // ->where('check_type', '<', $closestPeriod->end_at)
            ->latest('id')
            ->first()
        ;
        $lastCheckType = $latstAttendance->check_type;

        $dateTimeString = $latstAttendance->check_date . ' ' . $latstAttendance->check_time;
        $lastCheckTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString);

        $dateTimeString = $latstAttendance->check_date . ' ' . $periodEndTime;
        $carbonPeriodEndTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString);

        $currentDateTimeString = $currentDate . ' ' . $currentCheckTime;
        // $currentCheckTime = \Carbon\Carbon::parse($currentCheckTime);
        $currentCheckDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $currentDateTimeString);

        if ($lastCheckTime->gt($carbonPeriodEndTime) && $lastCheckType == Attendance::CHECKTYPE_CHECKOUT && $latstAttendance->is_from_previous_day) {
            return true;
        }
        return false;
    }

    private function checkIfSamePeriod($employeeId, $attendanceInPreviousDay, $period, $date, $checkTime)
    {

        $latstAttendance = Attendance::where('employee_id', $employeeId)
        // ->where('check_date', $date)
            ->select('id', 'check_type', 'check_date', 'check_time', 'period_id')
        // ->where('check_type', '<', $closestPeriod->end_at)
            ->latest('id')
            ->first()
        ;

        if ($latstAttendance && $latstAttendance->period_id == $period->id) {
            $isPreviousCompleted = $this->checkIfattendanceInPreviousDayIsCompleted($attendanceInPreviousDay, $period, $checkTime, $date);

            if (!$isPreviousCompleted) {
                return true;
            }
        }
        return false;
    }
}
