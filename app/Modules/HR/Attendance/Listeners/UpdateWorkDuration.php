<?php

namespace App\Modules\HR\Attendance\Listeners;

use App\Models\Attendance;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Events\CheckOutRecorded;
use Carbon\Carbon;

/**
 * مستمع تحديث مدة العمل
 * 
 * يستجيب لحدث تسجيل الخروج ويقوم بتحديث مدة العمل
 * يحتوي على منطق حساب المدة المفترضة والفعلية والإجمالية
 */
class UpdateWorkDuration
{
    public function __construct(
        private AttendanceRepositoryInterface $repository
    ) {}

    /**
     * معالجة الحدث
     */
    public function handle(CheckOutRecorded $event): void
    {
        $record = $event->record;

        // 1. المدة المفترضة من الوردية
        $this->setSupposedDuration($record);

        // 2. المدة الفعلية (للخروج فقط)
        if ($this->isCheckoutWithCheckIn($record)) {
            $this->calculateActualDuration($record);
        }

        // 3. إجمالي المدة الفعلية (للخروج فقط)
        if ($record->check_type === CheckType::CHECKOUT->value) {
            $this->calculateTotalDuration($record);
        }

        $record->save();
    }

    /**
     * تعيين المدة المفترضة من الوردية
     */
    private function setSupposedDuration(Attendance $record): void
    {
        if ($record->period) {
            $record->supposed_duration_hourly = $record->period->supposed_duration;
        }
    }

    /**
     * التحقق من أن السجل هو خروج ولديه سجل دخول مرتبط
     */
    private function isCheckoutWithCheckIn(Attendance $record): bool
    {
        return $record->check_type === CheckType::CHECKOUT->value
            && $record->checkinrecord_id;
    }

    /**
     * حساب المدة الفعلية بين الدخول والخروج
     */
    private function calculateActualDuration(Attendance $record): void
    {
        $checkIn = $this->repository->find($record->checkinrecord_id);

        if (!$checkIn) {
            return;
        }

        $checkInTime = Carbon::parse($checkIn->real_check_date . ' ' . $checkIn->check_time);
        $checkOutTime = Carbon::parse($record->real_check_date . ' ' . $record->check_time);

        $seconds = $checkInTime->diffInSeconds($checkOutTime);
        $record->actual_duration_hourly = $this->formatDuration($seconds);
    }

    /**
     * حساب إجمالي المدة الفعلية لليوم
     */
    private function calculateTotalDuration(Attendance $record): void
    {
        // جلب سجلات الخروج الأخرى لنفس اليوم
        $checkouts = $this->repository->getCheckoutsForDay(
            $record->employee_id,
            $record->period_id,
            $record->check_date,
            $record->id
        );

        $totalSeconds = 0;

        // جمع مدد الخروجات السابقة
        foreach ($checkouts as $checkout) {
            $totalSeconds += $this->parseDuration($checkout->actual_duration_hourly);
        }

        // إضافة مدة السجل الحالي
        $totalSeconds += $this->parseDuration($record->actual_duration_hourly);

        $record->total_actual_duration_hourly = $this->formatDuration($totalSeconds);
    }

    /**
     * تنسيق الثواني إلى صيغة HH:MM:SS
     */
    private function formatDuration(int $totalSeconds): string
    {
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * تحويل صيغة HH:MM:SS أو HH:MM إلى ثواني
     */
    private function parseDuration(?string $duration): int
    {
        if (!$duration) {
            return 0;
        }

        $parts = explode(':', $duration);
        $count = count($parts);

        if ($count === 3) {
            // HH:MM:SS
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
        } elseif ($count === 2) {
            // HH:MM (backwards compatibility)
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60);
        }

        return 0;
    }
}
