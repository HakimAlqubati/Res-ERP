<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Attendance;
use App\Modules\HR\Attendance\Actions\DetermineCheckTypeAction;
use App\Modules\HR\Attendance\Actions\UpdateDurationsAction;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\DTOs\AttendanceContextDTO;
use App\Modules\HR\Attendance\DTOs\AttendanceResultDTO;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Exceptions\NoShiftFoundException;

/**
 * معالج عمليات الحضور
 * 
 * يقوم بتنفيذ المنطق الأساسي لتسجيل الحضور:
 * 1. تحديد الوردية
 * 2. تحديد نوع العملية
 * 3. حساب التأخير/المغادرة
 * 4. حفظ السجل
 * 5. تحديث المدد
 */
class AttendanceHandler
{
    public function __construct(
        private ShiftResolverInterface $shiftResolver,
        private DetermineCheckTypeAction $determineCheckType,
        private AttendanceCalculator $calculator,
        private AttendanceRepositoryInterface $repository,
        private UpdateDurationsAction $updateDurations,
    ) {}

    /**
     * معالجة طلب الحضور
     */
    public function handle(AttendanceContextDTO $context): AttendanceResultDTO
    {
        // 1. تحديد الوردية
        $shiftInfo = $this->shiftResolver->resolve($context->employee, $context->requestTime);

        if (!$shiftInfo) {
            throw new NoShiftFoundException();
        }

        $context->setShiftInfo($shiftInfo);

        // 2. تحديد نوع العملية (دخول/خروج)
        // 2. تحديد نوع العملية (دخول/خروج)
        $context = $this->determineCheckType->execute($context);

        // التحقق من وجود سجل دخول عند الخروج
        if ($context->isCheckOut() && !$context->lastCheckIn) {
            throw new \App\Modules\HR\Attendance\Exceptions\MissingCheckInException();
        }

        // 3. حساب التأخير/المغادرة
        $context = $this->calculate($context);

        // 4. حفظ السجل
        $record = $this->persist($context);

        // 5. تحديث المدد
        $this->updateDurations->execute($record);

        // 6. إرجاع النتيجة
        return AttendanceResultDTO::success(
            message: $this->getSuccessMessage($context->checkType),
            record: $record->fresh()
        );
    }

    /**
     * حساب التأخير أو المغادرة المبكرة
     */
    private function calculate(AttendanceContextDTO $context): AttendanceContextDTO
    {
        if ($context->isCheckIn()) {
            return $this->calculator->calculateCheckIn($context);
        }

        return $this->calculator->calculateCheckOut($context);
    }

    /**
     * حفظ سجل الحضور
     */
    private function persist(AttendanceContextDTO $context): Attendance
    {
        return $this->repository->create($context->toCreateArray());
    }

    /**
     * الحصول على رسالة النجاح
     */
    private function getSuccessMessage(CheckType $checkType): string
    {
        return $checkType->isCheckIn()
            ? __('notifications.check_in_success')
            : __('notifications.check_out_success');
    }
}
