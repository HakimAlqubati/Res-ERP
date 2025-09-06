<?php
declare(strict_types=1);
namespace App\Attendance\Services;
use App\Attendance\DTO\AttendanceDTO;
use App\Attendance\Domain\Bounds;
use App\Attendance\Config\AttendanceConfig;
use Carbon\CarbonImmutable;
final class CheckInService {
    public function __construct(private TimeWindowService $tw, private AttendanceConfig $config) {}
    public function buildDTO(int $employeeId, int $periodId, string $deviceId, CarbonImmutable $nowTs, Bounds $b, string $checkDate, string $realCheckDate): AttendanceDTO {
        $late = $this->tw->minutesLateOnCheckIn($nowTs, $b);
        $early = $this->tw->minutesEarlyOnCheckIn($nowTs, $b);
        $status = 'ON_TIME';
        if ($late > 0) $status = 'LATE_ARRIVAL';
        elseif ($early > 0 && $early > $this->config->earlyArrivalMinutes()) $status = 'ON_TIME';
        return new AttendanceDTO($employeeId,$periodId,$checkDate,$realCheckDate,$nowTs->format('H:i:s'),
            'CHECKIN',$status,$late,$early,0,0,$b->isOvernight && $realCheckDate!==$checkDate,$deviceId);
    }
}
