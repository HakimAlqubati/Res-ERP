<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Attendance\DTO\AttendanceDTO;
use App\Attendance\Domain\Bounds;
use App\Attendance\Config\AttendanceConfig;
use Carbon\CarbonImmutable;
final class CheckOutService {
    public function __construct(private TimeWindowService $tw, private AttendanceConfig $config) {}
    public function buildDTO(int $employeeId, int $periodId, string $deviceId, CarbonImmutable $nowTs, Bounds $b, string $checkDate, string $realCheckDate): AttendanceDTO {
        $earlyDep = $this->tw->minutesEarlyOnCheckout($nowTs, $b);
        $lateDep  = $this->tw->minutesLateOnCheckout($nowTs, $b);
        $status = 'ON_TIME';
        if ($earlyDep > 0) $status = 'EARLY_DEPARTURE';
        elseif ($lateDep > 0 && $lateDep > $this->config->lateDepartureMinutes()) $status = 'LATE_DEPARTURE';
        else $lateDep = 0;
        return new AttendanceDTO($employeeId,$periodId,$checkDate,$realCheckDate,$nowTs->format('H:i:s'),
            'CHECKOUT',$status,0,0,$earlyDep,$lateDep,$b->isOvernight && $realCheckDate!==$checkDate,$deviceId);
    }
}
