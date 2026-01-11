<?php

namespace App\Modules\HR\Attendance\Events;

use App\Models\Attendance;
use App\Models\Employee;
use App\Modules\HR\Attendance\Enums\AttendanceStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث تسجيل الدخول
 * 
 * يُطلق عند تسجيل دخول ناجح للموظف
 */
class CheckInRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Attendance $record,
        public readonly Employee $employee,
        public readonly int $delayMinutes = 0,
        public readonly int $earlyArrivalMinutes = 0,
        public readonly ?AttendanceStatus $status = null,
    ) {}

    /**
     * التحقق من أن الموظف متأخر
     */
    public function isLate(): bool
    {
        return $this->delayMinutes > 0;
    }

    /**
     * التحقق من أن الموظف حضر مبكراً
     */
    public function isEarly(): bool
    {
        return $this->earlyArrivalMinutes > 0;
    }
}
