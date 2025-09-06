<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Attendance\Config\AttendanceConfig;
use App\Attendance\Domain\Bounds;
use App\Models\Attendance;
use Carbon\CarbonImmutable;
final class CheckTypeDecider {
    public function __construct(private AttendanceConfig $config) {}
    public function decide(int $employeeId, int $periodId, CarbonImmutable $nowTs, Bounds $bounds, string $checkDate): string {
        $preEnd = $bounds->periodEnd->subHours($this->config->preEndHours());
        if ($nowTs->greaterThanOrEqualTo($preEnd)) return 'CHECKOUT';
        $hasCheckIn = Attendance::query()
            ->where('employee_id', $employeeId)->where('period_id', $periodId)
            ->where('check_date', $checkDate)->where('type','CHECKIN')->where('accepted',1)->exists();
        return $hasCheckIn ? 'CHECKOUT' : 'CHECKIN';
    }
}
