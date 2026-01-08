<?php

namespace App\Modules\HR\Attendance\Events;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث اكتشاف التأخير
 * 
 * يُطلق عند اكتشاف تأخير في حضور الموظف
 * يمكن استخدامه لإرسال إشعارات أو تسجيل التأخيرات
 */
class LateArrivalDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Attendance $record,
        public readonly Employee $employee,
        public readonly int $delayMinutes,
    ) {}

    /**
     * الحصول على التأخير بالساعات
     */
    public function getDelayHours(): float
    {
        return round($this->delayMinutes / 60, 2);
    }

    /**
     * التحقق من أن التأخير كبير (أكثر من 15 دقيقة)
     */
    public function isSignificantDelay(): bool
    {
        return $this->delayMinutes > 15;
    }
}
