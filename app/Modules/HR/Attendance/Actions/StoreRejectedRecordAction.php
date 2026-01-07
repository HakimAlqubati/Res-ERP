<?php

namespace App\Modules\HR\Attendance\Actions;

use App\Models\Employee;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use Carbon\Carbon;

/**
 * Action لتخزين سجل الحضور المرفوض
 * 
 * يحفظ السجلات التي فشل قبولها لأسباب مختلفة
 * للمراجعة والتتبع لاحقاً
 */
class StoreRejectedRecordAction
{
    public function __construct(
        private AttendanceRepositoryInterface $repository
    ) {}

    /**
     * تنفيذ العملية
     */
    public function execute(
        Employee $employee,
        Carbon $requestTime,
        string $message,
        array $payload
    ): void {
        try {
            $periodId = $this->resolvePeriodId($employee, $requestTime);

            if (!$periodId) {
                return; // لا يمكن حفظ السجل بدون وردية
            }

            $attendanceType = $payload['attendance_type'] ?? 'rfid';

            $this->repository->createRejected(
                $employee,
                $requestTime,
                $message,
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
