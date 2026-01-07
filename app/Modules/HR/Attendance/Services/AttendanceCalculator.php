<?php

namespace App\Modules\HR\Attendance\Services;

use App\Modules\HR\Attendance\DTOs\AttendanceContextDTO;
use App\Modules\HR\Attendance\Enums\AttendanceStatus;
use Carbon\Carbon;

/**
 * خدمة حساب التأخير والمغادرة المبكرة
 * 
 * تقوم بحساب:
 * - دقائق التأخير
 * - دقائق الحضور المبكر
 * - دقائق المغادرة المبكرة
 * - دقائق العمل الإضافي (المغادرة المتأخرة)
 */
class AttendanceCalculator
{
    public function __construct(
        private AttendanceConfig $config
    ) {}

    /**
     * حساب بيانات تسجيل الدخول
     */
    public function calculateCheckIn(AttendanceContextDTO $context): AttendanceContextDTO
    {
        $checkTime = $context->requestTime;
        $shiftStart = $context->shiftInfo->start;
        $graceMinutes = $this->config->getGraceMinutes();

        if ($checkTime->lt($shiftStart)) {
            // الموظف حضر قبل بداية الشيفت
            $earlyMinutes = $checkTime->diffInMinutes($shiftStart);
            $context->earlyArrivalMinutes = $earlyMinutes;

            $context->setStatus(
                $earlyMinutes <= $graceMinutes
                    ? AttendanceStatus::ON_TIME
                    : AttendanceStatus::EARLY_ARRIVAL
            );
        } else {
            // الموظف حضر بعد بداية الشيفت أو في نفس الوقت
            $delayMinutes = $checkTime->diffInMinutes($shiftStart, true);

            if ($delayMinutes > 0) {
                $context->delayMinutes = $delayMinutes;

                $context->setStatus(
                    $delayMinutes <= $graceMinutes
                        ? AttendanceStatus::ON_TIME
                        : AttendanceStatus::LATE_ARRIVAL
                );
            } else {
                $context->setStatus(AttendanceStatus::ON_TIME);
            }
        }

        return $context;
    }

    /**
     * حساب بيانات تسجيل الخروج
     */
    public function calculateCheckOut(AttendanceContextDTO $context): AttendanceContextDTO
    {
        $checkTime = $context->requestTime;
        $shiftEnd = $context->shiftInfo->end;

        // حساب المدة الفعلية إذا كان هناك سجل دخول
        if ($context->lastCheckIn) {
            $context->actualMinutes = $this->calculateActualMinutes($context);
        }

        if ($checkTime->gt($shiftEnd)) {
            // المغادرة المتأخرة (عمل إضافي)
            $context->lateDepartureMinutes = $checkTime->diffInMinutes($shiftEnd, true);
            $context->setStatus(AttendanceStatus::LATE_DEPARTURE);
        } elseif ($checkTime->lt($shiftEnd)) {
            // المغادرة المبكرة
            $context->earlyDepartureMinutes = $checkTime->diffInMinutes($shiftEnd);
            $context->setStatus(AttendanceStatus::EARLY_DEPARTURE);
        } else {
            $context->setStatus(AttendanceStatus::ON_TIME);
        }

        return $context;
    }

    /**
     * حساب الدقائق الفعلية بين الدخول والخروج
     */
    private function calculateActualMinutes(AttendanceContextDTO $context): int
    {
        $checkIn = $context->lastCheckIn;

        $checkInTime = Carbon::parse($checkIn->check_date . ' ' . $checkIn->check_time);
        $checkOutTime = $context->requestTime;

        return $checkInTime->diffInMinutes($checkOutTime);
    }
}
