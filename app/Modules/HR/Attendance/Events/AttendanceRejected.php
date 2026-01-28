<?php

namespace App\Modules\HR\Attendance\Events;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث رفض تسجيل الحضور
 * 
 * يُطلق عند رفض تسجيل الحضور لأي سبب
 * مثل: تسجيل مكرر، عدم وجود وردية، إلخ
 */
class AttendanceRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Employee $employee,
        public readonly string $reason,
        public readonly Carbon $attemptTime,
        public readonly array $payload = [],
    ) {}
}
