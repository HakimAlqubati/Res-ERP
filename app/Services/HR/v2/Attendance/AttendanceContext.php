<?php

namespace App\Services\HR\v2\Attendance;

use App\Models\Employee;
use App\Models\WorkPeriod;
use App\Models\AttendanceImagesUploaded;
use Carbon\Carbon;

class AttendanceContext
{
    public function __construct(
        public Employee $employee,
        public Carbon $requestTime,
        public array $payload,
        public string $attendanceType,

        // مرجع المصدر (Polymorphic)
        public ?string $sourceType = null,
        public ?int $sourceId = null,

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
    ) {
        // Auto-resolve source for webcam attendance when not explicitly provided
        if ($this->attendanceType === 'webcam' && $this->sourceType === null && $this->sourceId === null) {
            $this->autoResolveWebcamSource();
        }
    }

    /**
     * البحث تلقائياً عن آخر صورة مرفوعة لنفس الموظف خلال آخر دقيقتين
     * هذا يربط الحضور بالصورة بدون الحاجة لتعديل تطبيق Flutter
     */
    private function autoResolveWebcamSource(): void
    {
        $recentImage = AttendanceImagesUploaded::where('employee_id', $this->employee->id)
            ->where('datetime', '>=', $this->requestTime->copy()->subMinutes(2))
            ->orderByDesc('datetime')
            ->first();

        if ($recentImage) {
            $this->sourceType = AttendanceImagesUploaded::class;
            $this->sourceId = $recentImage->id;
        }
    }

    public function setShift(WorkPeriod $period, string $date, string $day, array $bounds)
    {
        $this->workPeriod = $period;
        $this->shiftDate = $date;
        $this->shiftDayName = $day;
        $this->shiftBounds = $bounds;
    }
}
