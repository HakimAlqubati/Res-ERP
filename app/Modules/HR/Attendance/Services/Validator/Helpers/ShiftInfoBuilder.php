<?php

namespace App\Modules\HR\Attendance\Services\Validator\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * مساعد لبناء معلومات الشيفت
 */
class ShiftInfoBuilder
{
    /**
     * بناء معلومات الشيفت للـ exception
     */
    public static function buildShiftInfo(array $match): array
    {
        $period = $match['candidate']['period'];
        $bounds = $match['bounds'];

        return [
            'period_id' => $period->id,
            'name' => $period->name ?? __('notifications.shift'),
            'start' => $bounds['start']->format('H:i'),
            'end' => $bounds['end']->format('H:i'),
        ];
    }

    /**
     * بناء قائمة خيارات الورديات للـ exception
     */
    public static function buildShiftOptions(Collection $matchingShifts, Carbon $requestTime): Collection
    {
        return $matchingShifts->map(function ($match) use ($requestTime) {
            $period = $match['candidate']['period'];
            $bounds = $match['bounds'];

            return [
                'period_id' => $period->id,
                'name' => $period->name ?? __('notifications.shift'),
                'start' => $bounds['start']->format('H:i'),
                'end' => $bounds['end']->format('H:i'),
                'status' => self::getShiftStatus($bounds, $requestTime),
            ];
        });
    }

    /**
     * وصف حالة الوردية بالنسبة للوقت الحالي
     */
    public static function getShiftStatus(array $bounds, Carbon $requestTime): string
    {
        if ($requestTime->lt($bounds['start'])) {
            $diff = $requestTime->diffInMinutes($bounds['start']);
            return __('notifications.starts_in_minutes', ['minutes' => $diff]);
        }

        if ($requestTime->gte($bounds['end'])) {
            $diff = $requestTime->diffInMinutes($bounds['end']);
            return __('notifications.ended_minutes_ago', ['minutes' => $diff]);
        }

        return __('notifications.currently_active');
    }

    /**
     * البحث عن شيفت بـ period_id
     */
    public static function findShiftByPeriodId(Collection $matchingShifts, int $periodId): ?array
    {
        return $matchingShifts
            ->first(fn($match) => $match['candidate']['period']->id === $periodId);
    }

    /**
     * التحقق: هل الوقت في منطقة الفجوة بين الورديتين؟
     */
    public static function isTimeInGapZone(Collection $matchingShifts, Carbon $requestTime): bool
    {
        $sorted = $matchingShifts->sortBy(fn($m) => $m['bounds']['start']);
        $shifts = $sorted->values();

        $firstEnd = $shifts[0]['bounds']['end'];
        $secondStart = $shifts[1]['bounds']['start'];

        return $requestTime->gte($firstEnd) && $requestTime->lt($secondStart);
    }

    /**
     * البحث عن شيفت جديدة نشطة
     */
    public static function findActiveNewShift(Collection $matchingShifts, int $openShiftPeriodId, Carbon $requestTime): ?array
    {
        return $matchingShifts
            ->filter(function ($match) use ($openShiftPeriodId, $requestTime) {
                $periodId = $match['candidate']['period']->id;
                if ($periodId === $openShiftPeriodId) {
                    return false;
                }

                $bounds = $match['bounds'];
                return $requestTime->gte($bounds['start']) && $requestTime->lt($bounds['end']);
            })
            ->first();
    }
}
