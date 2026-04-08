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
        $totalDurationHours = sprintf(
            '%02d:%02d:%02d',
            floor($totalSecondsAllDays / 3600),
            ($totalSecondsAllDays / 60) % 60,
            $totalSecondsAllDays % 60
        );

        // =========================================================================
        // طرح أيام الإجازات الأسبوعية فقط إذا كان الفلتر شهر كامل
        // وكانت شيفتات الموظف تبدأ من أول الشهر أو قبله
        // يُطرح آخر يوم من كل أسبوع (يوم الإجازة) بساعاته الفعلية لا المتوسطة
        // =========================================================================
        $earliestHistoryStart = $histories->min('start_date');
        $employeeStartedFromBeginning = $earliestHistoryStart !== null
            && Carbon::parse($earliestHistoryStart)->lte($start);

        $isFullMonth = $start->day === 1
            && $end->day === $end->daysInMonth
            && $start->month === $end->month
            && $start->year === $end->year
            && $employeeStartedFromBeginning;

        if ($isFullMonth && count($days) > 4) {
            // نقسّم الأيام إلى أسابيع (كل 7 أيام) ونطرح آخر يوم من كل أسبوع مكتمل
            $deductionSeconds = 0;
            $chunks = $days->values()->chunk(7);

            foreach ($chunks as $week) {
                if ($week->count() < 7) {
                    continue; // تجاهل الأسبوع غير المكتمل
                }
                $lastDay = $week->last();
                if (
                    isset($lastDay['daily_duration_hours'])
                    && preg_match('/^\d{2}:\d{2}:\d{2}$/', $lastDay['daily_duration_hours'])
                ) {
                    [$h, $m, $s] = explode(':', $lastDay['daily_duration_hours']);
                    $deductionSeconds += ($h * 3600) + ($m * 60) + $s;
                }
            }

            $adjustedSeconds = max(0, $totalSecondsAllDays - $deductionSeconds);
            $totalDurationHours = sprintf(
                '%02d:%02d:%02d',
                floor($adjustedSeconds / 3600),
                ($adjustedSeconds / 60) % 60,
                $adjustedSeconds % 60
            );
        }

        return collect([
            'days'                 => $days,
            'total_duration_hours' => $totalDurationHours,
        ]);
    }

    protected function getDayOfWeekValue($day)
    {
        return is_object($day) && property_exists($day, 'value') ? $day->value : $day;
    }

    /**
     * إرجاع الفترات الفعّالة لمجموعة من الموظفين في تاريخ محدد (Batch)
     */
    public function getEmployeesPeriodsByDateBatch(Collection|array $employeeIds, Carbon $date): Collection
    {
        $employeeIds = is_array($employeeIds) ? $employeeIds : $employeeIds->toArray();
        $results = collect();

        // 1. جلب جميع السجلات التاريخية للموظفين المحددين في هذا التاريخ
        $histories = EmployeePeriodHistory::with('workPeriod')
            ->active()
            ->whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->get()
            ->groupBy('employee_id');

        $currentDay = strtolower($date->format('D'));
        $currentDayName = $date->translatedFormat('l');

        foreach ($employeeIds as $empId) {
            $employeeHistories = $histories->get($empId, collect());

            $matchingPeriods = $employeeHistories->filter(function ($history) use ($date, $currentDay) {
                return $this->getDayOfWeekValue($history->day_of_week) === $currentDay;
            })->map(function ($history) {
                $startRaw = $history->start_time ?? $history?->workPeriod?->start_at;
                $endRaw   = $history->end_time ?? $history?->workPeriod?->end_at;

                try {
                    $start = Carbon::parse($startRaw)->format('H:i:s');
                    $end = Carbon::parse($endRaw)->format('H:i:s');
                    
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

            $results->put($empId, [
                'days' => collect([
                    $date->toDateString() => [
                        'date'                 => $date->toDateString(),
                        'day_name'             => $currentDayName,
                        'daily_duration_hours' => $dailyDuration,
                        'periods'              => $matchingPeriods->values(),
                    ]
                ]),
                'total_duration_hours' => $dailyDuration, // بما أنه يوم واحد، الإجمالي هو نفسه اليومي
            ]);
        }

        return $results;
    }
}
