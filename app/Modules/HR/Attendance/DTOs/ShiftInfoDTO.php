<?php

namespace App\Modules\HR\Attendance\DTOs;

use App\Models\WorkPeriod;
use Carbon\Carbon;

/**
 * DTO لمعلومات الوردية
 * 
 * يحتوي على جميع المعلومات المتعلقة بالوردية المحددة
 */
final readonly class ShiftInfoDTO
{
    public function __construct(
        public WorkPeriod $period,
        public string $date,
        public string $dayName,
        public Carbon $start,
        public Carbon $end,
        public Carbon $windowStart,
        public Carbon $windowEnd,
    ) {}

    /**
     * إنشاء من مصفوفة (للتوافق مع الكود القديم)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            period: $data['period'],
            date: $data['date'],
            dayName: $data['day'],
            start: $data['bounds']['start'],
            end: $data['bounds']['end'],
            windowStart: $data['bounds']['windowStart'],
            windowEnd: $data['bounds']['windowEnd'],
        );
    }

    /**
     * تحويل إلى مصفوفة
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'date' => $this->date,
            'day' => $this->dayName,
            'bounds' => [
                'start' => $this->start,
                'end' => $this->end,
                'windowStart' => $this->windowStart,
                'windowEnd' => $this->windowEnd,
            ],
        ];
    }

    /**
     * الحصول على معرف الوردية
     */
    public function getPeriodId(): int
    {
        return $this->period->id;
    }

    /**
     * التحقق من أن الوقت ضمن نافذة السماحية
     */
    public function isWithinWindow(Carbon $time): bool
    {
        return $time->betweenIncluded($this->windowStart, $this->windowEnd);
    }

    /**
     * التحقق من أن الوقت بعد نهاية الوردية
     */
    public function isAfterShiftEnd(Carbon $time): bool
    {
        return $time->greaterThan($this->end);
    }

    /**
     * التحقق من أن الوقت قبل بداية الوردية
     */
    public function isBeforeShiftStart(Carbon $time): bool
    {
        return $time->lessThan($this->start);
    }
}
