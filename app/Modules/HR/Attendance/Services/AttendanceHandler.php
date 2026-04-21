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
            $this->createAutoMissedCheckoutRequest($context);

            return AttendanceResultDTO::autoRequestCreated(
                __('lang.auto_missed_checkout_request_created_success', ['default' => 'تم إنشاء طلب نسيان دخول تلقائياً لعدم وجود بصمة، يرجى مراجعته من الموارد البشرية.'])
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
     * إنشاء طلب نسيان خروج تلقائي
     */
    private function createAutoMissedCheckoutRequest(AttendanceContextDTO $context): void
    {
        $application = \App\Models\EmployeeApplicationV2::create([
            'employee_id' => $context->employee->id,
            'branch_id' => $context->employee->branch_id,
            'application_date' => $context->requestTime->toDateString(),
            'status' => \App\Models\EmployeeApplicationV2::STATUS_PENDING,
            'application_type_id' => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
            'application_type_name' => \App\Models\EmployeeApplicationV2::APPLICATION_TYPE_NAMES[\App\Models\EmployeeApplicationV2::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST],
            'created_by' => auth()->id() ?? $context->employee->user_id ?? 0,
            'is_auto_generated' => true,
        ]);

        \App\Models\MissedCheckOutRequest::create([
            'application_id' => $application->id,
            'application_type_id' => $application->application_type_id,
            'application_type_name' => $application->application_type_name,
            'employee_id' => $context->employee->id,
            'date' => $context->shiftDate ?? $context->requestTime->toDateString(),
            'time' => $context->requestTime->format('H:i'),
            'reason' => __('lang.auto_generated_reason_missing_checkin', ['default' => 'تم الإنشاء تلقائياً بسبب تسجيل انصراف بدون بصمة دخول']),
            'is_auto_generated' => true,
        ]);
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
