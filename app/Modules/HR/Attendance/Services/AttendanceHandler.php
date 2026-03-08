<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Attendance;
use App\Modules\HR\Attendance\Actions\DetermineCheckTypeAction;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\DTOs\AttendanceContextDTO;
use App\Modules\HR\Attendance\DTOs\AttendanceResultDTO;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Events\CheckInRecorded;
use App\Modules\HR\Attendance\Events\CheckOutRecorded;
use App\Modules\HR\Attendance\Events\LateArrivalDetected;
use App\Modules\HR\Attendance\Exceptions\NoShiftFoundException;
use App\Modules\HR\Attendance\DTOs\ShiftInfoDTO;

/**
 * معالج عمليات الحضور
 * 
 * يقوم بتنفيذ المنطق الأساسي لتسجيل الحضور:
 * 1. تحديد الوردية
 * 2. تحديد نوع العملية
 * 3. حساب التأخير/المغادرة
 * 4. حفظ السجل
 * 5. إطلاق الأحداث (تحديث المدد يتم عبر Listener)
 */
class AttendanceHandler
{
    public function __construct(
        private ShiftResolverInterface $shiftResolver,
        private DetermineCheckTypeAction $determineCheckType,
        private AttendanceCalculator $calculator,
        private AttendanceRepositoryInterface $repository,
    ) {}

    /**
     * معالجة طلب الحضور
     */
    public function handle(AttendanceContextDTO $context): AttendanceResultDTO
    {
        // 1. تحديد الوردية
        // 1. تحديد الوردية
        $periodId = $context->payload['period_id'] ?? null;

        $shiftInfo = $this->shiftResolver->resolve(
            $context->employee,
            $context->requestTime,
            $this->repository,
            $periodId
        );

        // إذا تم تحديد فترة ولم يتم العثور عليها (غير مطابقة)
        if ($periodId && !$shiftInfo) {
            throw new \App\Modules\HR\Attendance\Exceptions\ShiftMismatchException();
        }

        if (!$shiftInfo) {
            throw new NoShiftFoundException();
        }

        if (!$shiftInfo) {
            throw new NoShiftFoundException();
        }
        $context->setShiftInfo($shiftInfo);

        // 2. تحديد نوع العملية (دخول/خروج)
        $requestedType = $context->getRequestedCheckType();
        if ($requestedType) {
            $context->setCheckType($requestedType);

            if ($context->isCheckOut()) {
                $lastCheckIn = $this->repository->findOpenCheckIn(
                    $context->employee->id,
                    $context->workPeriod->id,
                    $context->shiftDate
                );
                $context->setLastCheckIn($lastCheckIn);
            }
        } else {
            $context = $this->determineCheckType->execute($context);
        }

        // التحقق من وجود سجل دخول عند الخروج
        if ($context->isCheckOut() && !$context->lastCheckIn) {
            throw new \App\Modules\HR\Attendance\Exceptions\MissingCheckInException(
                $shiftInfo->period->name ?? null
            );
        }

        // 3. حساب التأخير/المغادرة
        $context = $this->calculate($context);

        // 4. حفظ السجل
        $record = $this->persist($context);
        // 5. إطلاق الأحداث
        $this->dispatchEvents($record, $context);

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
     * إطلاق الأحداث المناسبة
     */
    private function dispatchEvents(Attendance $record, AttendanceContextDTO $context): void
    {
        if ($context->isCheckIn()) {
            // إطلاق حدث تسجيل الدخول
            CheckInRecorded::dispatch(
                $record,
                $context->employee,
                $context->delayMinutes,
                $context->earlyArrivalMinutes,
                $context->status
            );

            // إطلاق حدث التأخير إذا وجد
            if ($context->delayMinutes > 0) {
                LateArrivalDetected::dispatch(
                    $record,
                    $context->employee,
                    $context->delayMinutes
                );
            }
        } else {
            // إطلاق حدث تسجيل الخروج
            CheckOutRecorded::dispatch(
                $record,
                $context->employee,
                $context->actualMinutes,
                $context->lateDepartureMinutes,
                $context->earlyDepartureMinutes,
                $context->status
            );
        }
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
