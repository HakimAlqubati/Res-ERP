<?php

namespace App\Modules\HR\Attendance\Exceptions;

/**
 * Exception عند وجود تعارض بين شيفت مفتوحة وشيفت جديدة
 * 
 * يُستخدم عندما يكون للموظف check-in مفتوح في شيفت
 * والوقت الحالي ضمن نافذة شيفت أخرى
 * ويتطلب من المستخدم تحديد العملية المطلوبة صراحةً
 */
class ShiftConflictException extends AttendanceException
{
    public function __construct(
        public readonly array $openShift,
        public readonly array $newShift,
        string $message = ''
    ) {
        $message = $message ?: __('notifications.shift_conflict_detected');
        parent::__construct($message);
    }

    /**
     * الحصول على خيارات العمليات المتاحة
     */
    public function getOptions(): array
    {
        return [
            [
                'action' => 'checkout',
                'period_id' => $this->openShift['period_id'],
                'name' => $this->openShift['name'],
                'start' => $this->openShift['start'],
                'end' => $this->openShift['end'],
                'description' => __('notifications.checkout_from_shift', ['shift' => $this->openShift['name']]),
            ],
            [
                'action' => 'checkin',
                'period_id' => $this->newShift['period_id'],
                'name' => $this->newShift['name'],
                'start' => $this->newShift['start'],
                'end' => $this->newShift['end'],
                'description' => __('notifications.checkin_to_shift', ['shift' => $this->newShift['name']]),
            ],
        ];
    }

    /**
     * الحصول على معلومات الشيفت المفتوحة
     */
    public function getOpenShift(): array
    {
        return $this->openShift;
    }

    /**
     * الحصول على معلومات الشيفت الجديدة
     */
    public function getNewShift(): array
    {
        return $this->newShift;
    }
}
