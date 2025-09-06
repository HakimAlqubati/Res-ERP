<?php
declare(strict_types=1);
namespace App\Attendance\Domain;
use App\Attendance\Config\AttendanceConfig;
use App\Models\WorkPeriod;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
final class BoundsCalculator {
    public function __construct(private AttendanceConfig $config) {}
    public function compute(WorkPeriod $period, CarbonImmutable $now): Bounds {
        $start = $this->anchorTime($now, $period->start_time);
        $end   = $this->anchorTime($now, $period->end_time);
        $isOvernight = false;
        if ($end->lessThanOrEqualTo($start)) { $end = $end->addDay(); $isOvernight = true; }
        $windowStart = $start->subHours($this->config->allowedBeforeHours());
        $windowEnd   = $end->addHours($this->config->allowedAfterHours());
        return new Bounds($start, $end, $windowStart, $windowEnd, $isOvernight);
    }
    private function anchorTime(CarbonImmutable $ref, string $hhmmss): CarbonImmutable {
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $hhmmss)) throw new InvalidArgumentException("Invalid time: {$hhmmss}");
        [$h,$m,$s] = array_map('intval', explode(':', $hhmmss)); return $ref->setTime($h,$m,$s);
    }
}
