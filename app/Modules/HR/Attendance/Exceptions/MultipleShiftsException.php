<?php

namespace App\Modules\HR\Attendance\Exceptions;

use Illuminate\Support\Collection;

/**
 * Exception عند وجود أكثر من وردية متاحة
 * 
 * يُستخدم عندما يكون الوقت المطلوب ضمن نوافذ ورديات متعددة متداخلة
 * ويتطلب من المستخدم تحديد الوردية المطلوبة صراحةً
 */
class MultipleShiftsException extends AttendanceException
{
    public function __construct(
        public readonly Collection $availableShifts,
        string $message = ''
    ) {
        $message = $message ?: __('notifications.multiple_shifts_available');
        parent::__construct($message);
    }

    /**
     * تحويل الورديات المتاحة إلى مصفوفة للـ API
     */
    public function getShiftsArray(): array
    {
        return $this->availableShifts->map(function ($shift) {
            return [
                'period_id' => $shift['period_id'],
                'name' => $shift['name'],
                'start' => $shift['start'],
                'end' => $shift['end'],
                'status' => $shift['status'] ?? null,
            ];
        })->values()->toArray();
    }
}
