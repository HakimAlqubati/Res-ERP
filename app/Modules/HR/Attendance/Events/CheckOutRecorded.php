<?php

namespace App\Modules\HR\Attendance\Events;

use App\Models\Attendance;
use App\Models\Employee;
use App\Modules\HR\Attendance\Enums\AttendanceStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث تسجيل الخروج
 * 
 * يُطلق عند تسجيل خروج ناجح للموظف
 */
class CheckOutRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Attendance $record,
        public readonly Employee $employee,
        public readonly int $actualMinutes = 0,
        public readonly int $lateDepartureMinutes = 0,
        public readonly int $earlyDepartureMinutes = 0,
        public readonly ?AttendanceStatus $status = null,
    ) {}

    /**
     * التحقق من أن الموظف غادر مبكراً
     */
    public function isEarlyDeparture(): bool
    {
        return $this->earlyDepartureMinutes > 0;
    }

    /**
     * التحقق من أن الموظف غادر متأخراً (عمل إضافي)
     */
    public function isOvertime(): bool
    {
        return $this->lateDepartureMinutes > 0;
    }

    /**
     * الحصول على مدة العمل بالساعات
     */
    public function getActualHours(): float
    {
        return round($this->actualMinutes / 60, 2);
    }
}
