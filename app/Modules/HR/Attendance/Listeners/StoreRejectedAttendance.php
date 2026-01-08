<?php

namespace App\Modules\HR\Attendance\Listeners;

use App\Models\Employee;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Events\AttendanceRejected;
use Carbon\Carbon;

/**
 * مستمع تخزين سجل الحضور المرفوض
 * 
 * يستجيب لحدث رفض الحضور ويقوم بحفظ السجل في قاعدة البيانات
 * للمراجعة والتتبع لاحقاً
 */
class StoreRejectedAttendance
{
    public function __construct(
        private AttendanceRepositoryInterface $repository
    ) {}

    /**
     * معالجة الحدث
     */
    public function handle(AttendanceRejected $event): void
    {
        try {
            $periodId = $this->resolvePeriodId($event->employee, $event->attemptTime);

            if (!$periodId) {
                return; // لا يمكن حفظ السجل بدون وردية
            }

            $attendanceType = $event->payload['attendance_type'] ?? 'rfid';

            $this->repository->createRejected(
                $event->employee,
                $event->attemptTime,
                $event->reason,
                $periodId,
                $attendanceType
            );
        } catch (\Throwable) {
            // Silent fail - لا نريد أن يفشل النظام بسبب تسجيل السجل المرفوض
        }
    }

    /**
     * تحديد الوردية المناسبة للسجل المرفوض
     */
    private function resolvePeriodId(Employee $employee, Carbon $time): ?int
    {
        $dayName = $time->format('l');

        // محاولة إيجاد وردية لهذا اليوم
        $period = $employee->periods()
            ->whereJsonContains('days', $dayName)
            ->first();

        // إذا لم نجد، نستخدم أي وردية للموظف
        if (!$period) {
            $period = $employee->periods()->first();
        }

        return $period?->id;
    }
}
