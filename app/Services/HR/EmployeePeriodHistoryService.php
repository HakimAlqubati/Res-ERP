<?php 
namespace App\Services\HR;

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
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereNull('end_date')
                    ->orWhereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                          ->where('end_date', '>=', $end);
                    });
            })
            ->get();
 

        // لكل يوم في النطاق الزمني
        while ($date->lte($end)) {
            $currentDay = strtolower($date->format('D')); // ex: 'mon', 'tue'
            $matchingPeriods = $histories->filter(function ($history) use ($date, $currentDay) {
                $dayMatch = $history->day_of_week === $currentDay;
                
                $startOk = Carbon::parse($history->start_date)->lte($date);
                $endOk = $history->end_date === null || Carbon::parse($history->end_date)->gte($date);

                return $dayMatch && $startOk && $endOk;
            })->map(function ($history) {
                
                return [
                    'period_id' => $history->period_id,
                    'name'      => optional($history->workPeriod)->name,
                    'start_time'=> $history->start_time,
                    'end_time'  => $history->end_time,
                ];
            });

            $days->put($date->toDateString(), $matchingPeriods->values());

            $date->addDay();
        }

        return $days;
    }
}