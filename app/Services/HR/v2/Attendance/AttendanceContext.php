<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Employee;
use App\Models\WorkPeriod;
use Carbon\Carbon;

class AttendanceContext
{
    public function __construct(
        public Employee $employee,
        public Carbon $requestTime,
        public array $payload,
        public string $attendanceType,

        // Resolved State
        public ?WorkPeriod $workPeriod = null,
        public ?string $shiftDate = null,      // The logical date of the shift (e.g., 2023-10-01 even if check-out is 2023-10-02)
        public ?string $shiftDayName = null,   // 'sun', 'mon', etc.
        public ?array $shiftBounds = null,     // Start/End times including grace periods

        // Previous State
        public ?\App\Models\Attendance $lastAction = null,     // Last attendance record (CheckIn)

        // Output State
        public ?string $checkType = null,      // 'checkin' or 'checkout'
        public ?string $status = null,         // 'on_time', 'late_arrival', etc.
        public int $delayMinutes = 0,
        public int $earlyArrivalMinutes = 0,
        public int $lateDepartureMinutes = 0,
        public int $earlyDepartureMinutes = 0,
        public int $actualMinutes = 0,
    ) {}

    public function setShift(WorkPeriod $period, string $date, string $day, array $bounds)
    {
        $this->workPeriod = $period;
        $this->shiftDate = $date;
        $this->shiftDayName = $day;
        $this->shiftBounds = $bounds;
    }
}
