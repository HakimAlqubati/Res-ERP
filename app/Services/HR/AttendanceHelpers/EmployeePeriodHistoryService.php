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
            // $currentDayName = $currentDay;   // 'Monday' أو 'الاثنين'
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
}