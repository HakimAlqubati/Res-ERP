<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Employee;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Exceptions\AttendanceCompletedException;
use App\Modules\HR\Attendance\Exceptions\DuplicateCheckInException;
use App\Modules\HR\Attendance\Exceptions\MissingCheckInException;
use App\Modules\HR\Attendance\Exceptions\TypeRequiredException;
use Carbon\Carbon;

/**
 * خدمة التحقق من قواعد العمل للحضور
 * 
 * تفحص جميع قواعد العمل قبل السماح بتسجيل الحضور:
 * - هل الوردية مكتملة؟
 * - هل يوجد تسجيل دخول مكرر؟
 * - هل يحاول الخروج بدون دخول؟
 * - هل يحتاج تحديد نوع العملية؟
 */
class AttendanceValidator
{
    public function __construct(
        private ShiftResolverInterface $shiftResolver,
        private AttendanceRepositoryInterface $repository,
        private AttendanceConfig $config
    ) {}

    /**
     * التحقق من صحة طلب الحضور
     * 
     * @throws AttendanceCompletedException
     * @throws DuplicateCheckInException
     * @throws MissingCheckInException
     * @throws TypeRequiredException
     */
    public function validate(Employee $employee, Carbon $requestTime, ?string $requestType = null): void
    {
        // تحديد الوردية والتاريخ المناسب
        $shiftInfo = $this->shiftResolver->resolve($employee, $requestTime);
        $date = $shiftInfo?->date ?? $requestTime->toDateString();
        $periodId = $shiftInfo?->getPeriodId();

        // جلب السجلات اليومية
        $dailyRecords = $this->repository->getDailyRecords($employee->id, $date);

        // فلترة حسب الوردية إذا وجدت
        $shiftRecords = $periodId
            ? $dailyRecords->where('period_id', $periodId)
            : $dailyRecords;

        $checkInRecord = $shiftRecords->firstWhere('check_type', CheckType::CHECKIN->value);
        $checkOutRecord = $shiftRecords
            ->sortByDesc('check_time')
            ->firstWhere('check_type', CheckType::CHECKOUT->value);

        // القاعدة 1: الوردية مكتملة
        if ($checkInRecord && $checkOutRecord) {
            $this->validateShiftNotCompleted($checkInRecord, $checkOutRecord, $requestTime, $date);
        }

        // القاعدة 2: تكرار الدخول
        if ($requestType === CheckType::CHECKIN->value && $checkInRecord) {
            throw new DuplicateCheckInException();
        }

        // القاعدة 3: الخروج بدون دخول
        if ($requestType === CheckType::CHECKOUT->value && !$checkInRecord) {
            throw new MissingCheckInException();
        }

        // القاعدة 4: الطلب قرب نهاية الشيفت بدون سجل دخول
        if (!$checkInRecord && $requestType === null && $shiftInfo) {
            $this->validateNearShiftEnd($shiftInfo, $requestTime);
        }
    }

    /**
     * التحقق من أن الوردية غير مكتملة
     */
    private function validateShiftNotCompleted($checkIn, $checkOut, Carbon $requestTime, string $date): void
    {
        $bounds = $this->shiftResolver->calculateBounds($checkIn->period, $checkIn->check_date);

        // هل انتهت فترة السماحية؟
        $isGraceExpired = $requestTime->gt($bounds['windowEnd']);

        // هل تم الخروج بعد نهاية الدوام؟
        $isShiftCompleted = $this->isCheckoutAfterShiftEnd($checkOut, $bounds['end']);

        if ($isGraceExpired || $isShiftCompleted) {
            throw new AttendanceCompletedException($date);
        }
    }

    /**
     * التحقق من أن وقت الخروج بعد نهاية الوردية
     */
    private function isCheckoutAfterShiftEnd($checkOut, Carbon $shiftEnd): bool
    {
        $datePart = $checkOut->real_check_date ?? $checkOut->check_date;
        $checkoutTime = Carbon::parse($datePart . ' ' . $checkOut->check_time);

        return $checkoutTime->greaterThanOrEqualTo($shiftEnd);
    }

    /**
     * التحقق من الطلبات قرب نهاية الشيفت بدون سجلات
     */
    private function validateNearShiftEnd($shiftInfo, Carbon $requestTime): void
    {
        $hoursBeforePeriod = $this->config->getPreEndHoursForCheckInOut();
        $thresholdMinutes = $hoursBeforePeriod * 60;

        $thresholdTime = $shiftInfo->end->copy()->subMinutes($thresholdMinutes);

        if ($requestTime->gte($thresholdTime)) {
            throw new TypeRequiredException();
        }
    }
}
