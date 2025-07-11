<?php
namespace App\Services\HR\AttendanceHelpers;

use App\Models\Employee;
use App\Models\EmployeePeriodHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeePeriodHistoryService
{
    /**
     * إرجاع الفترات الفعّالة لكل يوم داخل المدى الزمني للموظف
     */

    public function getEmployeePeriodsByDateRange(Employee $employee, Carbon $start, Carbon $end): Collection
    {
        $days = collect();
        $date = $start->copy();

        // حمل جميع السجلات التاريخية للموظف في المدى الزمني مرة واحدة
        $histories = EmployeePeriodHistory::with('workPeriod')
            ->active()
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start);
            })

            ->get();

        // لكل يوم في النطاق الزمني
        while ($date->lte($end)) {
            $currentDay     = strtolower($date->format('D')); // ex: 'mon', 'tue'
            $currentDayName = $date->translatedFormat('l');   // 'Monday' أو 'الاثنين'

            $matchingPeriods = $histories->filter(function ($history) use ($date, $currentDay) {
                $dayMatch = $this->getDayOfWeekValue($history->day_of_week) === $currentDay;
                $startOk  = Carbon::parse($history->start_date)->lte($date);
                $endOk    = $history->end_date === null || Carbon::parse($history->end_date)->gte($date);
                return $dayMatch && $startOk && $endOk;
            })->map(function ($history) {
                return [
                    'period_id'  => $history->period_id,
                    'name'       => optional($history->workPeriod)->name,
                    'start_time' => $history->start_time ?? $history?->workPeriod?->start_at,
                    'end_time'   => $history->end_time ?? $history?->workPeriod?->end_at,
                ];
            });

            $days->put($date->toDateString(), [
                'date'     => $date->toDateString(),
                'day_name' => $currentDayName,
                'periods'  => $matchingPeriods->values(),
            ]);

            $date->addDay();
        }

        return $days;
    }

    protected function getDayOfWeekValue($day)
    {
        return is_object($day) && property_exists($day, 'value') ? $day->value : $day;
    }
    public function getEmployeePeriodsWithAttendanceByDateRange(Employee $employee, Carbon $start, Carbon $end): Collection
    {
        $days = collect();

        // استخرج كل فترات الموظف (التاريخية) للنطاق دفعة واحدة
        $histories = EmployeePeriodHistory::with('workPeriod')
            ->active()
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start);
            })
            ->get();

        // مر على كل يوم في النطاق
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $currentDay     = strtolower($date->format('D'));
            $currentDayName = $date->translatedFormat('l');

            // الفترات لهذا اليوم
            $matchingPeriods = $histories->filter(function ($history) use ($date, $currentDay) {
                $dayMatch = $this->getDayOfWeekValue($history->day_of_week) === $currentDay;
                $startOk  = Carbon::parse($history->start_date)->lte($date);
                $endOk    = $history->end_date === null || Carbon::parse($history->end_date)->gte($date);
                return $dayMatch && $startOk && $endOk;
            })->map(function ($history) use ($employee, $date) {
                // استخرج الحضور (attendance) لهذه الفترة وذلك اليوم
                $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
                    ->where('period_id', $history->period_id)
                    ->whereDate('check_date', $date->toDateString())
                    ->where('accepted', 1)
                    ->orderBy('check_time')
                    ->get();

                // أول Check-in
                $firstCheckin = $attendances->where('check_type', 'checkin')->first();
                // آخر Check-out
                $lastCheckout = $attendances->where('check_type', 'checkout')->last();

                return [
                    'period_id'  => $history->period_id,
                    'name'       => optional($history->workPeriod)->name,
                    'start_time' => $history->start_time ?? $history?->workPeriod?->start_at,
                    'end_time'   => $history->end_time ?? $history?->workPeriod?->end_at,
                    // بيانات التحضير (الحضور)
                    'attendance' => [
                        'first_checkin' => $firstCheckin ? [
                            'check_time' => $firstCheckin->check_time,
                            'status'     => $firstCheckin->status,
                        ] : null,
                        'last_checkout' => $lastCheckout ? [
                            'check_time' => $lastCheckout->check_time,
                            'status'     => $lastCheckout->status,
                        ] : null,
                        'all_records'   => $attendances->map(function ($att) {
                            return [
                                'check_type' => $att->check_type,
                                'check_time' => $att->check_time,
                                'status'     => $att->status,
                            ];
                        }),
                    ],
                ];
            })->values();

            $days->put($date->toDateString(), [
                'date'     => $date->toDateString(),
                'day'      => $currentDay,
                'day_name' => $currentDayName,
                'periods'  => $matchingPeriods,
            ]);
        }

        return $days;
    }

}