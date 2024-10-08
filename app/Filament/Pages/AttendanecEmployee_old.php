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

class AttendanecEmployee_old extends BasePage
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
                    ->timezone('Asia/Kuala_Lumpur')
                    ->prefixIcon('heroicon-m-check-circle')
                    ->prefixIconColor('success')
                    ->required()->seconds(false),
                TextInput::make('rfid')
                    ->autocomplete(false)
                    ->label('Employee RFID')
                    ->prefixIcon('heroicon-m-check-circle')
                    ->prefixIconColor('success')
                    ->label('قم بإدخال رقم التحضير  الخاص بك واضغط على زر البصمة')
                    ->required()
                    ->placeholder('RFID')
                    ->maxLength(255),
            ])->statePath('data');
    }

    public function submit()
    {

        // // Get the previous URL
        // $previousUrl = url()->previous();

        // // Create a request object from the previous URL
        // $request = Request::create($previousUrl);

        // // Retrieve the route name and parameters
        // $route = Route::getRoutes()->match($request);

        // $date = $route->parameters['date'] ?? null;
        // $time = $route->parameters['time'] ?? null;

 
        $formData = $this->form->getState();

        $this->storeAttendanceEmployee($formData);
    }

    public function storeAttendanceEmployee($data)
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
                return Notification::make()
                    ->title(' معذرة  ' . $employee->name)
                    ->body('لا يوجد لديك فترات فهذا اليوم  (' . $day . ')')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->warning()
                    ->duration(10000)
                    ->send();
            }
            $this->handleAttendance($employee, $time, $date, $day, $periodsForDay);

        } elseif (!is_null($employee) && count($employeePeriods) == 0) {
            return Notification::make()
                ->title(' مرحباً  ' . $employee->name)
                ->body('نأسف, لم يتم إضافة أي فترات دوام إليك, يرجى التواصل مع الإدارة!')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->warning()
                ->duration(10000)
                ->send();

        } else {
            return Notification::make()
                ->title('خطأ!')
                ->body(' لا يوجد موظف بهذا الرقم  ' . $data['rfid'])
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->warning()
                ->duration(10000)
                ->send();

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
// dd($closestPeriod);
        if (!$closestPeriod) {
            // No period found, so we return with an error or handle accordingly
            return 'No valid period found for the given time.';
        }

        // Step 2: Check if attendance exists for this period, date, and day
        $existAttendance = $this->getExistingAttendance($employee, $closestPeriod, $date, $day);

        // Step 3: Determine the action based on attendance count
        $attendanceCount = $existAttendance->count();

        if ($attendanceCount === 0) {

            // dupple Check for periods from the previous night
            $previousDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
            $previousDayName = \Carbon\Carbon::parse($date)->subDay()->format('l');
            // $previousDay = \Carbon\Carbon::parse($previousDay)->format('l');
            $previousDayPeriods = $employee->periods->filter(function ($period) use ($previousDayName) {
                return in_array($previousDayName, $period->days);
            });
            $existAttendanceInPrevoiusDay = $this->getExistingAttendance($employee, $closestPeriod, $previousDate, $previousDayName);

            $closestPreviousPeriod = $this->findClosestPeriod($time, $previousDayPeriods);
 
            // Find if there is any relevant previous day in current period
            if ($existAttendanceInPrevoiusDay->count() == 1 && $existAttendanceInPrevoiusDay[0]->check_type == Attendance::CHECKTYPE_CHECKIN) {
                return $this->createAttendance($employee, $closestPreviousPeriod, $previousDate, $time, $previousDayName, Attendance::CHECKTYPE_CHECKOUT);
            }
            // No attendance, this will be a 'checkin'
            return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKIN);
        } elseif ($attendanceCount === 1 && $existAttendance[0]->check_date == $date) {
            // One attendance record found, this will be a 'checkout'
            return $this->createAttendance($employee, $closestPeriod, $date, $time, $day, Attendance::CHECKTYPE_CHECKOUT);
        } else {
            // Two or more attendance records, nothing to do
            return Notification::make()
                ->title('مرحباً ' . $employee->name)
                ->body('تم تسجيل حضور وانصراف لهذه الفترة')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->warning()
                ->duration(10000)
                ->send();
        }

    }

    private function createAttendance($employee, $nearestPeriod, $date, $checkTime, $day, $checkType)
    {

        $allowedLateMinutes = $nearestPeriod?->allowed_count_minutes_late;
        $startTime = \Carbon\Carbon::parse($nearestPeriod->start_at);
        $endTime = \Carbon\Carbon::parse($nearestPeriod->end_at);

        // Ensure that $checkTime is a Carbon instance
        $checkTime = \Carbon\Carbon::parse($checkTime);

        // Handle check-in scenario
        if ($checkType == Attendance::CHECKTYPE_CHECKIN) {
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
            $data['late_departure_minutes'] = 0; // Initialize for check-in
        }

        // Handle check-out scenario
        elseif ($checkType == Attendance::CHECKTYPE_CHECKOUT) {
            // Find the corresponding check-in record
            $checkinRecord = Attendance::where('employee_id', $employee->id)
                ->where('period_id', $nearestPeriod->id)
                ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                ->whereDate('check_date', $date) // Use the provided check date
                ->first();

            if ($checkinRecord) {
                $checkinTime = \Carbon\Carbon::parse($checkinRecord->check_time);

                // Calculate the actual duration (from check-in to check-out)
                $actualDuration = $checkinTime->diff($checkTime);
                $hoursActual = $actualDuration->h;
                $minutesActual = $actualDuration->i;

                // Calculate the supposed duration (from period start to end)
                $supposedDuration = $startTime->diff($endTime);
                $hoursSupposed = $supposedDuration->h;
                $minutesSupposed = $supposedDuration->i;

                // Store both durations in a format like "hours:minutes"
                $data['actual_duration_hourly'] = sprintf('%02d:%02d', $hoursActual, $minutesActual);
                $data['supposed_duration_hourly'] = sprintf('%02d:%02d', $hoursSupposed, $minutesSupposed);
            }

            // Calculate late departure or early departure
            if ($checkTime->gt($endTime)) {
                // Late departure
                $data['late_departure_minutes'] = $endTime->diffInMinutes($checkTime);
                $data['early_departure_minutes'] = 0;
                $data['status'] = Attendance::STATUS_LATE_DEPARTURE;
            } else if($endTime->gt($checkTime)) {
                // Early departure
                $data['late_departure_minutes'] = 0;
                $data['early_departure_minutes'] = $checkTime->diffInMinutes($endTime);
                $data['status'] = Attendance::STATUS_EARLY_DEPARTURE;
            }else{
                $data['late_departure_minutes'] = 0;
                $data['early_departure_minutes'] = 0;
                $data['status'] = Attendance::STATUS_ON_TIME;

            }
            $data['delay_minutes'] = 0; // Initialize for check-out
        }

        $data2 = $data;
        $data2['employee_id'] = $employee->id;
        $data2['period_id'] = $nearestPeriod->id;
        $data2['check_date'] = $date;
        $data2['check_time'] = $checkTime;
        $data2['day'] = $day;
        $data2['check_type'] = $checkType;
        $data2['branch_id'] = $employee?->branch?->id;
        $data2['created_by'] = 0;
        // dd($data2);

        Attendance::create($data2);

        if ($checkType == Attendance::CHECKTYPE_CHECKIN) {
            $checkType = 'الحضور';
        } else {
            $checkType = 'الانصراف';
        }
        return Notification::make()
            ->title(' مرحباً موظفنا العزيز  ' . $employee->name)
            ->body(' لقد تم تسجيل ' . $checkType)
            ->icon('heroicon-o-check-circle')
            ->iconSize(IconSize::Large)
            ->iconPosition(IconPosition::Before)
            ->duration(10000)
            ->iconColor('success')
            ->success()
            ->send();

    }

    private function getExistingAttendance($employee, $closestPeriod, $date, $day)
    {
        return Attendance::where('employee_id', $employee->id)
            ->where('period_id', $closestPeriod->id) // Using array key if closestPeriod is an array
            ->where('check_date', $date)
            ->where('day', $day)
            ->select('check_type', 'check_date')
            ->get();
    }

    /**
     * to create notification
     */
    private function createNotification($checkType, $employee)
    {

    }
}
