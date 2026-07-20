<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Attendance;
use App\Modules\HR\Attendance\Actions\CreateMissedCheckoutRequestAction;
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
use App\Modules\HR\Attendance\Enums\AttendanceType;

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
        private CreateMissedCheckoutRequestAction $createMissedCheckoutRequest,
        private AttendanceConfig $config,
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
            $hasShiftToday = $this->shiftResolver->hasShiftOnDate($context->employee, clone $context->requestTime);

            if ($hasShiftToday || !$this->config->isNoShiftAttendanceAllowed()) {
                throw new NoShiftFoundException();
            }
            // الإعداد مفعّل والموظف بدون ورديات اليوم: نكمل بدون شيفت (period_id = null)
        } else {
            $context->setShiftInfo($shiftInfo);
        }

        // 2. تحديد نوع العملية (دخول/خروج)
        // ملاحظة: عند وجود شيفت وnوع صريح يُعالج هنا مباشرة؛
        // أما حالة بدون شيفت تمر عبر DetermineCheckTypeAction لأنه يعرف كيف يبحث بدون period_id
        if ($context->getRequestedCheckType() && $context->workPeriod) {
            $context->setCheckType($context->getRequestedCheckType());

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

        // Check-out with no open check-in
        if ($context->isCheckOut() && !$context->lastCheckIn) {

            // إذا كان الطلب من نوع "request" (موافقة على طلب انصراف):
            // لا يمكن تسجيل خروج بدون دخول مسجّل مسبقاً — نُعيد failure DTO مباشرةً
            // (نفس نمط autoRequestCreated أدناه، بدون رمي exception)
            if ($context->attendanceType->value === AttendanceType::REQUEST->value) {
                return AttendanceResultDTO::failure(
                    __('notifications.cannot_checkout_without_checkin')
                );
            }

            // للحضور العادي (fingerprint/rfid/...): إنشاء طلب انصراف تلقائي لمراجعة HR.
            $this->createMissedCheckoutRequest->execute($context);

            return AttendanceResultDTO::autoRequestCreated(
                __('lang.auto_missed_checkout_request_created_success', ['default' => 'Auto-generated missed check-out request created. HR will review it shortly.'])
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
