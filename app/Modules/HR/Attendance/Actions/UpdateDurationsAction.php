<?php

namespace App\Modules\HR\Attendance\Actions;

use App\Models\Attendance;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Enums\CheckType;
use Carbon\Carbon;

/**
 * Action لتحديث مدد الحضور
 * 
 * يقوم بحساب:
 * 1. المدة المفترضة (من الوردية)
 * 2. المدة الفعلية (للخروج)
 * 3. إجمالي المدة الفعلية (لليوم)
 */
class UpdateDurationsAction
{
    public function __construct(
        private AttendanceRepositoryInterface $repository
    ) {}

    /**
     * تنفيذ العملية
     */
    public function execute(Attendance $record): Attendance
    {
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

        return $record;
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

        $minutes = $checkInTime->diffInMinutes($checkOutTime);
        $record->actual_duration_hourly = $this->formatMinutes($minutes);
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

        $totalMinutes = 0;

        // جمع مدد الخروجات السابقة
        foreach ($checkouts as $checkout) {
            $totalMinutes += $this->parseMinutes($checkout->actual_duration_hourly);
        }

        // إضافة مدة السجل الحالي
        $totalMinutes += $this->parseMinutes($record->actual_duration_hourly);

        $record->total_actual_duration_hourly = $this->formatMinutes($totalMinutes);
    }

    /**
     * تنسيق الدقائق إلى صيغة HH:MM
     */
    private function formatMinutes(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * تحويل صيغة HH:MM إلى دقائق
     */
    private function parseMinutes(?string $duration): int
    {
        if (!$duration) {
            return 0;
        }

        $parts = explode(':', $duration);

        if (count($parts) < 2) {
            return 0;
        }

        return ((int) $parts[0] * 60) + (int) $parts[1];
    }
}
