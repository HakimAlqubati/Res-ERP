<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Attendance\Domain\Bounds;
use Carbon\CarbonImmutable;
final class TimeWindowService {
    public function isWithinWindow(CarbonImmutable $ts, Bounds $b): bool { return $ts->betweenIncluded($b->windowStart, $b->windowEnd); }
    public function minutesDiff(CarbonImmutable $a, CarbonImmutable $b): int { return (int) abs($a->diffInMinutes($b, false)); }
    public function minutesLateOnCheckIn(CarbonImmutable $ts, Bounds $b): int { return $ts->lessThanOrEqualTo($b->periodStart) ? 0 : (int) $b->periodStart->diffInMinutes($ts, false); }
    public function minutesEarlyOnCheckIn(CarbonImmutable $ts, Bounds $b): int { return $ts->greaterThanOrEqualTo($b->periodStart) ? 0 : (int) $ts->diffInMinutes($b->periodStart, false); }
    public function minutesEarlyOnCheckout(CarbonImmutable $ts, Bounds $b): int { return $ts->greaterThanOrEqualTo($b->periodEnd) ? 0 : (int) $b->periodEnd->diffInMinutes($ts, false); }
    public function minutesLateOnCheckout(CarbonImmutable $ts, Bounds $b): int { return $ts->lessThanOrEqualTo($b->periodEnd) ? 0 : (int) $b->periodEnd->diffInMinutes($ts, false); }
}
