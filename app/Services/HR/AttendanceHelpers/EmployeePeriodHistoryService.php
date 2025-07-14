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
            $currentDay = strtolower($date->format('D'));   // ex: 'mon', 'tue'
                                                            // $currentDayName = $currentDay;   // 'Monday' أو 'الاثنين'
            $currentDayName = $date->translatedFormat('l'); // 'Monday' أو 'الاثنين'

            $matchingPeriods = $histories->filter(function ($history) use ($date, $currentDay) {
                $dayMatch = $this->getDayOfWeekValue($history->day_of_week) === $currentDay;
                $startOk  = Carbon::parse($history->start_date)->lte($date);
                $endOk    = $history->end_date === null || Carbon::parse($history->end_date)->gte($date);
                return $dayMatch && $startOk && $endOk;
            })->map(function ($history) {
                $startRaw = $history->start_time ?? $history?->workPeriod?->start_at;
                $endRaw   = $history->end_time ?? $history?->workPeriod?->end_at;

                try {
                    $start = Carbon::parse($startRaw)->format('H:i:s');
                } catch (\Exception $e) {
                    $start = '00:00:00';
                }
                try {
                    $end = Carbon::parse($endRaw)->format('H:i:s');
                } catch (\Exception $e) {
                    $end = '00:00:00';
                }

                try {
                    $startCarbon = Carbon::createFromFormat('H:i:s', $start);
                    $endCarbon   = Carbon::createFromFormat('H:i:s', $end);

                    if ($history?->workPeriod?->day_and_night == 1) {
                        $endCarbon->addDay();
                    }

                    $diffInSeconds    = $startCarbon->diffInSeconds($endCarbon);
                    $supposedDuration = gmdate('H:i:s', $diffInSeconds);
                } catch (\Exception $e) {
                    $supposedDuration = '00:00:00';
                }

                return [
                    'period_id'         => $history->period_id,
                    'name'              => optional($history->workPeriod)->name,
                    'start_time'        => $history->start_time ?? $history?->workPeriod?->start_at,
                    'end_time'          => $history->end_time ?? $history?->workPeriod?->end_at,
                    'supposed_duration' => $supposedDuration,
                ];
            });
            $totalSeconds = $matchingPeriods->reduce(function ($carry, $period) {
                list($h, $m, $s) = explode(':', $period['supposed_duration']);
                return $carry + ($h * 3600) + ($m * 60) + $s;
            }, 0);
            $dailyDuration = gmdate('H:i:s', $totalSeconds);
            $days->put($date->toDateString(), [
                'date'                 => $date->toDateString(),
                'day_name'             => $currentDayName,
                'daily_duration_hours' => $dailyDuration,
                'periods'              => $matchingPeriods->values(),
            ]);

            $date->addDay();
        }

        $totalSecondsAllDays = 0;
        foreach ($days as $day) {
            if (
                is_array($day)
                && isset($day['daily_duration_hours'])
                && preg_match('/^\d{2}:\d{2}:\d{2}$/', $day['daily_duration_hours'])
            ) {
                list($h, $m, $s) = explode(':', $day['daily_duration_hours']);
                $totalSecondsAllDays += ($h * 3600) + ($m * 60) + $s;
            }
        }
        $totalDurationHours = sprintf('%02d:%02d:%02d',
            floor($totalSecondsAllDays / 3600),
            ($totalSecondsAllDays / 60) % 60,
            $totalSecondsAllDays % 60
        );
// الناتج هنا سيكون 31:00:00
        
// يمكنك إرجاع النتيجة كمصفوفة فيها كل الأيام والمجموع الكلي، هكذا:
        return collect([
            'days'                 => $days,
            'total_duration_hours' => $totalDurationHours,
        ]);
    }

    protected function getDayOfWeekValue($day)
    {
        return is_object($day) && property_exists($day, 'value') ? $day->value : $day;
    }
}