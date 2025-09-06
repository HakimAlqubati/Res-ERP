<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Attendance\DTO\AttendanceResult;
use Carbon\CarbonImmutable;
final class AttendanceValidator {
    public function __construct(private AttendanceFetcher $fetcher) {}
    public function guardRateLimit(int $employeeId, CarbonImmutable $now, bool $isRequest): ?AttendanceResult {
        if ($isRequest) return null;
        $last = $this->fetcher->latestFor($employeeId); if (!$last) return null;
        $tz = $now->tz;
        $lastTs = CarbonImmutable::parse(($last->real_check_date ?? $last->check_date) . ' ' . $last->check_time, $tz);
        if ($now->diffInSeconds($lastTs) < 60) return AttendanceResult::fail('Duplicate attempt within one minute.');
        return null;
    }
    public function ensureMonthIsOpen(string $checkDate): ?AttendanceResult {
        return null;
    }
}
