<?php

namespace App\Services\HR;

use App\Models\Attendance;
use App\Models\EmployeePeriod;
use App\Models\EmployeePeriodHistory;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class EmployeeWorkPeriodService
{
    /**
     * Assign work periods to an employee.
     *
     * @param \App\Models\Employee $employee
     * @param array $data Expected keys: 'periods' (array of IDs), 'start_date', 'end_date' (optional), 'period_days' (array)
     * @return void
     * @throws Exception
     */
    public function assignPeriodsToEmployee($employee, array $data)
    {
        DB::beginTransaction();
        try {
            $selectedPeriodsWithDates = [];
            foreach ($data['periods'] as $periodId) {
                $selectedPeriodsWithDates[] = [
                    'period_id'  => $periodId,
                    'start_date' => $data['start_date'],
                    'end_date'   => $data['end_date'] ?? null,
                ];
            }

            if ($this->isInternalPeriodsOverlappingWithDates($selectedPeriodsWithDates)) {
                Notification::make()
                    ->title('Overlapping Error')
                    ->body('There are overlapping shifts with overlapping periods and times. Please check your selection.')
                    ->danger()
                    ->send();
                // Depending on how we want to handle this, we might throw an exception instead of returning
                // But since the original code was in a closure that returns void/null, we need to signal failure.
                // The original code used `return;` to stop execution.
                // Here we should probably throw an exception to be caught by the controller/action, 
                // OR duplicate the notification logic if we want to keep it consistent.
                // Given the user wants "use from anywhere", throwing exceptions is cleaner for APIs, 
                // but for Filament actions, notifications are often embedded. 
                // I will throw an exception to allow the caller to handle UI feedback, 
                // OR I can keep the notification if this is primarily for Filament.
                // Let's stick closer to the original logic but make it reusable. 
                // I will throw valid exceptions and let the caller catch them.
                throw new Exception('There are overlapping shifts with overlapping periods and times. Please check your selection.');
            }

            // Validate the employee's last attendance
            $lastAttendance = $employee->attendances()->latest('id')->first();
            if ($lastAttendance && $lastAttendance->check_type === Attendance::CHECKTYPE_CHECKIN) {
                // Original commented out code kept for reference if needed
            }

            $dataPeriods = array_map('intval', $data['periods']);

            // Insert new periods into hr_employee_periods table
            foreach ($dataPeriods as $value) {
                $workPeriod    = WorkPeriod::find($value);
                $periodStartAt = $workPeriod?->start_at;
                $periodEndAt   = $workPeriod?->end_at;

                // أيام الفترة المراد إدخالها
                $periodDays = $data['period_days'] ?? [];

                if ($this->isOverlappingDays_(
                    $employee->id,
                    $periodDays,
                    $periodStartAt,
                    $periodEndAt,
                    $data['start_date'],
                    $data['end_date'] ?? null,
                )) {
                    throw new Exception('❌ Cannot add this Work Period as it overlaps with an existing period.');
                }

                $employeePeriod              = new EmployeePeriod();
                $employeePeriod->employee_id = $employee->id;
                $employeePeriod->period_id   = $value;
                $employeePeriod->start_date  = $data['start_date'];
                $employeePeriod->end_date    = $data['end_date'] ?? null;
                $employeePeriod->save();

                foreach ($data['period_days'] as $dayOfWeek) {

                    $employeePeriod->days()->create([
                        'day_of_week' => $dayOfWeek,

                    ]);

                    EmployeePeriodHistory::create([
                        'employee_id' => $employee->id,
                        'period_id'   => $value,
                        'start_date'  => $data['start_date'],
                        'end_date'    => $data['end_date'] ?? null,
                        'start_time'  => $periodStartAt,
                        'end_time'    => $periodEndAt,
                        'day_of_week' => $dayOfWeek,
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function isInternalPeriodsOverlappingWithDates($selectedPeriodsWithDates)
    {
        $periods = WorkPeriod::whereIn('id', array_column($selectedPeriodsWithDates, 'period_id'))
            ->get()
            ->keyBy('id');

        $count = count($selectedPeriodsWithDates);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $selectedPeriodsWithDates[$i];
                $b = $selectedPeriodsWithDates[$j];

                $periodA = $periods[$a['period_id']];
                $periodB = $periods[$b['period_id']];

                // ✅ استخدم Carbon وحسب day_and_night
                $aStart = Carbon::createFromFormat('H:i:s', $periodA->start_at);
                $aEnd   = Carbon::createFromFormat('H:i:s', $periodA->end_at);
                if ($periodA->day_and_night) {
                    $aEnd->addDay();
                }

                $bStart = Carbon::createFromFormat('H:i:s', $periodB->start_at);
                $bEnd   = Carbon::createFromFormat('H:i:s', $periodB->end_at);
                if ($periodB->day_and_night) {
                    $bEnd->addDay();
                }

                // تحقق من التداخل
                $timesOverlap = ($aStart <= $bEnd) && ($bStart <= $aEnd);

                // تحقق من التواريخ
                $aEndDate = $a['end_date'] ?? null;
                $bEndDate = $b['end_date'] ?? null;
                $datesOverlap =
                    ($aEndDate === null || $b['start_date'] <= $aEndDate) &&
                    ($bEndDate === null || $a['start_date'] <= $bEndDate);

                if ($timesOverlap && $datesOverlap) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isOverlappingDays_(
        $employeeId,
        $periodDays,
        $periodStartAt,
        $periodEndAt,
        $periodStartDate,
        $periodEndDate = null,
        $excludePeriodId = null
    ) {
        $query = EmployeePeriod::query()
            ->with([
                'days' => function ($q) use ($periodDays) {
                    $q->whereIn('day_of_week', $periodDays);
                },
                'workPeriod', // إضافة علاقة الشيفت
            ])
            ->where('employee_id', $employeeId)
            ->where(function ($q) use ($periodStartDate, $periodEndDate) {
                $q->where(function ($q2) use ($periodStartDate, $periodEndDate) {
                    // شرط تقاطع الفترات
                    $q2->whereNull('end_date')->orWhere(function ($q3) use ($periodStartDate, $periodEndDate) {
                        if ($periodEndDate) {
                            $q3->where('start_date', '<=', $periodEndDate)
                                ->where(function ($q4) use ($periodStartDate) {
                                    $q4->whereNull('end_date')->orWhere('end_date', '>=', $periodStartDate);
                                });
                        } else {
                            $q3->where('end_date', '>=', $periodStartDate)->orWhereNull('end_date');
                        }
                    });
                });
            });

        if ($excludePeriodId) {
            $query->where('id', '!=', $excludePeriodId);
        }

        $overlappingPeriods = $query->get();

        // أوقات الفترة الحالية المراد إضافتها
        $currentStart = Carbon::createFromFormat('H:i:s', $periodStartAt);
        $currentEnd   = Carbon::createFromFormat('H:i:s', $periodEndAt);

        // إذا الشيفت جديد يمتد لليوم التالي، عدل النهاية
        $currentWorkPeriodModel = \App\Models\WorkPeriod::where('start_at', $periodStartAt)
            ->where('end_at', $periodEndAt)->first();

        $currentDayAndNight = $currentWorkPeriodModel?->day_and_night ?? 0;
        if ($currentDayAndNight) {
            $currentEnd->addDay();
        }

        foreach ($overlappingPeriods as $period) {
            $wp = $period->workPeriod;
            if (! $wp) {
                continue;
            }

            $existStart = Carbon::createFromFormat('H:i:s', $wp->start_at);
            $existEnd   = Carbon::createFromFormat('H:i:s', $wp->end_at);

            if ($wp->day_and_night) {
                $existEnd->addDay();
            }

            // تحقق تداخل الأوقات
            $timesOverlap = ($currentStart <= $existEnd) && ($existStart <= $currentEnd);

            // يوجد يوم متداخل && أوقات متداخلة
            if ($timesOverlap && $period->days->count()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Assign additional days to an existing EmployeePeriod.
     *
     * @param EmployeePeriod $employeePeriod
     * @param array $days Array of day_of_week values to add
     * @return void
     * @throws Exception
     */
    public function assignDaysToEmployeePeriod(EmployeePeriod $employeePeriod, array $days): void
    {
        DB::beginTransaction();
        try {
            foreach ($days as $day) {
                $employeePeriod->days()->create([
                    'day_of_week' => $day,
                ]);

                EmployeePeriodHistory::create([
                    'employee_id' => $employeePeriod->employee_id,
                    'period_id'   => $employeePeriod->period_id,
                    'start_date'  => $employeePeriod->start_date,
                    'end_date'    => $employeePeriod->end_date,
                    'start_time'  => $employeePeriod->workPeriod->start_at,
                    'end_time'    => $employeePeriod->workPeriod->end_at,
                    'day_of_week' => $day,
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
