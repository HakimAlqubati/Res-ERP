<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Attendance;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\DTOs\AttendanceContextDTO;
use App\Modules\HR\Attendance\DTOs\AttendanceResultDTO;
use App\Modules\HR\Attendance\Enums\AttendanceStatus;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Enums\ShiftlessAttendanceMode;
use App\Modules\HR\Attendance\Events\CheckInRecorded;
use App\Modules\HR\Attendance\Events\CheckOutRecorded;
use App\Modules\HR\Attendance\Exceptions\ShiftlessAttendanceDisabledException;
use App\Modules\HR\Attendance\Exceptions\TypeRequiredException;

/**
 * معالج الحضور بدون شيفت (Shiftless Attendance Handler)
 *
 * مسؤولية هذا الـ Handler:
 * ─ التحقق من أن وضع الحضور بدون شيفت مُفعَّل في إعدادات النظام
 * ─ تحديد نوع العملية (دخول/خروج) بالاعتماد على وجود open check-in (بدون period_id)
 * ─ تسجيل سجل الحضور بـ period_id = null
 * ─ إطلاق الأحداث المناسبة (CheckInRecorded / CheckOutRecorded)
 *
 * ما لا يفعله هذا الـ Handler عمداً:
 * ─ لا يحسب تأخيراً أو مغادرةً مبكرة (لا يوجد مرجع زمني من شيفت)
 * ─ لا يُنشئ طلب missed-checkout (منطق مرتبط بالشيفتات فقط)
 */
class ShiftlessAttendanceHandler
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $repository,
        private readonly AttendanceConfig $config,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────

    /**
     * معالجة طلب الحضور بدون شيفت
     *
     * @throws ShiftlessAttendanceDisabledException عندما يكون الوضع = DENY
     * @throws TypeRequiredException عندما يكون الوضع = REQUIRE_TYPE بدون تحديد النوع
     */
    public function handle(AttendanceContextDTO $context): AttendanceResultDTO
    {
        $mode = $this->config->getShiftlessAttendanceMode();

        $this->assertAllowed($mode);
        $this->assertTypeProvidedWhenRequired($mode, $context);

        $context = $this->resolveCheckType($context);

        if ($this->isMissingCheckIn($context)) {
            return $this->missingCheckInResponse();
        }

        $record = $this->persist($context);

        $this->dispatchEvents($record, $context);

        return AttendanceResultDTO::success(
            message: $this->getSuccessMessage($context->checkType),
            record: $record->fresh()
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Guards
    // ─────────────────────────────────────────────────────────────

    /**
     * التحقق من أن الوضع يسمح بالحضور بدون شيفت
     *
     * @throws ShiftlessAttendanceDisabledException
     */
    private function assertAllowed(ShiftlessAttendanceMode $mode): void
    {
        if (!$mode->isAllowed()) {
            throw new ShiftlessAttendanceDisabledException();
        }
    }

    /**
     * التحقق من تحديد النوع صراحةً عندما يكون الوضع REQUIRE_TYPE
     *
     * @throws TypeRequiredException
     */
    private function assertTypeProvidedWhenRequired(
        ShiftlessAttendanceMode $mode,
        AttendanceContextDTO $context
    ): void {
        if ($mode->requiresExplicitType() && !$context->getRequestedCheckType()) {
            throw new TypeRequiredException();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Check Type Resolution
    // ─────────────────────────────────────────────────────────────

    /**
     * تحديد نوع العملية (دخول/خروج)
     * يُفضَّل النوع المحدد صراحةً — وإلا يُحدَّد تلقائياً
     */
    private function resolveCheckType(AttendanceContextDTO $context): AttendanceContextDTO
    {
        $requestedType = $context->getRequestedCheckType();

        return $requestedType
            ? $this->handleExplicitType($context, $requestedType)
            : $this->handleAutoDetect($context);
    }

    private function handleExplicitType(
        AttendanceContextDTO $context,
        CheckType $type
    ): AttendanceContextDTO {
        $context->setCheckType($type);

        if ($context->isCheckOut()) {
            $lastCheckIn = $this->repository->findOpenShiftlessCheckIn(
                $context->employee->id,
                $context->requestTime->toDateString()
            );
            $context->setLastCheckIn($lastCheckIn);
        }

        return $context;
    }

    private function handleAutoDetect(AttendanceContextDTO $context): AttendanceContextDTO
    {
        $lastCheckIn = $this->repository->findOpenShiftlessCheckIn(
            $context->employee->id,
            $context->requestTime->toDateString()
        );

        if ($lastCheckIn) {
            $context->setCheckType(CheckType::CHECKOUT);
            $context->setLastCheckIn($lastCheckIn);
        } else {
            $context->setCheckType(CheckType::CHECKIN);
        }

        return $context;
    }

    // ─────────────────────────────────────────────────────────────
    // Persistence
    // ─────────────────────────────────────────────────────────────

    private function persist(AttendanceContextDTO $context): Attendance
    {
        // لا يوجد شيفت → نستخدم تاريخ ويوم الطلب مباشرة
        // (شيفت داء setShiftInfo() مسؤولة عن تعبئة هذه الحقول — نعوّضها هنا)
        $context->shiftDate    = $context->requestTime->toDateString();
        $context->shiftDayName = strtolower($context->requestTime->format('D'));

        // لا يوجد مرجع شيفت → الحالة دائماً ON_TIME
        $context->setStatus(AttendanceStatus::ON_TIME);
// dd($context->toCreateArray());
        return $this->repository->create($context->toCreateArray());
    }

    // ─────────────────────────────────────────────────────────────
    // Events
    // ─────────────────────────────────────────────────────────────

    private function dispatchEvents(Attendance $record, AttendanceContextDTO $context): void
    {
        if ($context->isCheckIn()) {
            CheckInRecorded::dispatch(
                $record,
                $context->employee,
                delayMinutes: 0,
                earlyArrivalMinutes: 0,
                status: $context->status
            );

            return;
        }

        CheckOutRecorded::dispatch(
            $record,
            $context->employee,
            actualMinutes: $context->actualMinutes,
            lateDepartureMinutes: 0,
            earlyDepartureMinutes: 0,
            status: $context->status
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * هل الموظف يحاول الخروج دون دخول مسجَّل؟
     */
    private function isMissingCheckIn(AttendanceContextDTO $context): bool
    {
        return $context->isCheckOut() && !$context->lastCheckIn;
    }

    private function missingCheckInResponse(): AttendanceResultDTO
    {
        return AttendanceResultDTO::failure(
            __(
                'notifications.missing_check_in_shiftless',
                ['default' => 'No open check-in found for today. Please check in first.']
            )
        );
    }

    private function getSuccessMessage(CheckType $checkType): string
    {
        return $checkType->isCheckIn()
            ? __('notifications.check_in_success')
            : __('notifications.check_out_success');
    }
}
