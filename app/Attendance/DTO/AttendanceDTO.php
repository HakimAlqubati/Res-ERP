<?php
declare(strict_types=1);
namespace App\Attendance\DTO;
final class AttendanceDTO {
    public function __construct(
        public int $employeeId,
        public int $periodId,
        public string $checkDate,
        public string $realCheckDate,
        public string $checkTime,
        public string $type,
        public string $status,
        public int $delayMinutes = 0,
        public int $earlyArrivalMinutes = 0,
        public int $earlyDepartureMinutes = 0,
        public int $lateDepartureMinutes = 0,
        public bool $fromPreviousDay = false,
        public ?string $deviceId = null
    ) {}
}
