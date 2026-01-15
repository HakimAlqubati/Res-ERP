<?php

namespace App\Modules\HR\Attendance\Services;

use App\Models\Employee;
use App\Modules\HR\Attendance\Contracts\AttendanceRepositoryInterface;
use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Exceptions\AttendanceCompletedException;
use App\Modules\HR\Attendance\Exceptions\DuplicateCheckInException;
use App\Modules\HR\Attendance\Exceptions\DuplicateTimestampException;
use App\Modules\HR\Attendance\Exceptions\MissingCheckInException;
use App\Modules\HR\Attendance\Exceptions\MultipleShiftsException;
use App\Modules\HR\Attendance\Exceptions\TypeRequiredException;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * خدمة التحقق من قواعد العمل للحضور
 * 
 * تفحص جميع قواعد العمل قبل السماح بتسجيل الحضور بشكل منظم ومرتب
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
     * @throws DuplicateTimestampException
     * @throws MissingCheckInException
     * @throws TypeRequiredException
     */
    public function validate(Employee $employee, Carbon $requestTime, ?string $requestType = null, ?int $periodId = null): void
    {
        // 1. تحديد الشيفت والسجلات
        $context = $this->prepareValidationContext($employee, $requestTime, $periodId);

        // 2. تطبيق قواعد التحقق بالترتيب

        // القاعدة 0: منع التسجيل في نفس الدقيقة → رفض التسجيل المتكرر بسرعة
        $this->validateDuplicateTimestamp($context->lastRecord, $requestTime);

        // القاعدة 1: التحقق من اكتمال الشيفت → رفض إذا انتهت فترة السماحية
        $this->validateShiftCompletion($context, $requestTime);

        // القاعدة 2: منع تكرار الدخول → رفض check-in مكرر
        $this->validateDuplicateCheckIn($requestType, $context->lastIsCheckIn);

        // القاعدة 3: منع الخروج بدون دخول → رفض check-out بدون check-in
        $this->validateMissingCheckIn($requestType, $context);


        // القاعدة 4: التحقق من الورديات المتداخلة → يتطلب اختيار الوردية (قبل التحقق من نهاية الشيفت)
        $this->validateOverlappingShifts($employee, $requestTime, $context, $periodId);

        // القاعدة 5: طلب تحديد النوع قرب نهاية الشيفت → يتطلب type عند الغموض
        $this->validateNearShiftEndWithoutRecords($requestType, $context, $requestTime);
    }

    // ═══════════════════════════════════════════════════════════════
    // تحضير سياق التحقق
    // ═══════════════════════════════════════════════════════════════

    /**
     * تحضير معلومات السياق اللازمة للتحقق
     */
    private function prepareValidationContext(Employee $employee, Carbon $requestTime, ?int $periodId = null): object
    {
        // تحديد الوردية (مع تمرير period_id إذا كان محدداً صراحةً)
        $shiftInfo = $this->shiftResolver->resolve($employee, $requestTime, $this->repository, $periodId);
        $date = $shiftInfo?->date ?? $requestTime->toDateString();
        $periodId = $shiftInfo?->getPeriodId();

        // جلب السجلات
        $dailyRecords = $this->repository->getDailyRecords($employee->id, $date);
        $shiftRecords = $periodId
            ? $dailyRecords->where('period_id', $periodId)
            : $dailyRecords;

        // تحليل السجلات
        $lastRecord = $shiftRecords->sortByDesc('id')->first();

        return (object) [
            'shiftInfo' => $shiftInfo,
            'date' => $date,
            'shiftRecords' => $shiftRecords,
            'lastRecord' => $lastRecord,
            'lastIsCheckIn' => $lastRecord && $lastRecord->check_type === CheckType::CHECKIN->value,
            'lastIsCheckOut' => $lastRecord && $lastRecord->check_type === CheckType::CHECKOUT->value,
            'hasAnyCheckIn' => $shiftRecords->where('check_type', CheckType::CHECKIN->value)->isNotEmpty(),
            'hasAnyCheckOut' => $shiftRecords->where('check_type', CheckType::CHECKOUT->value)->isNotEmpty(),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // قواعد التحقق
    // ═══════════════════════════════════════════════════════════════

    /**
     * القاعدة 0: منع التسجيل في نفس الدقيقة بالضبط
     */
    private function validateDuplicateTimestamp($lastRecord, Carbon $requestTime): void
    {
        if (!$lastRecord) {
            return;
        }

        $lastCheckTime = Carbon::parse($lastRecord->check_date . ' ' . $lastRecord->check_time);
        $lastMinute = $lastCheckTime->format('Y-m-d H:i');
        $currentMinute = $requestTime->format('Y-m-d H:i');

        if ($lastMinute === $currentMinute) {
            $nextMinute = $requestTime->copy()->addMinute()->startOfMinute();
            $remainingSeconds = $requestTime->diffInSeconds($nextMinute);
            $timeDisplay = $this->formatRemainingTime($remainingSeconds);

            throw new DuplicateTimestampException(
                __('notifications.duplicate_timestamp_not_allowed', [
                    'seconds' => $timeDisplay
                ])
            );
        }
    }

    /**
     * القاعدة 1: التحقق من اكتمال الوردية
     */
    private function validateShiftCompletion(object $context, Carbon $requestTime): void
    {
        if (!($context->hasAnyCheckIn && $context->lastIsCheckOut)) {
            return;
        }

        $checkInRecord = $context->shiftRecords->firstWhere('check_type', CheckType::CHECKIN->value);
        $checkOutRecord = $context->shiftRecords
            ->sortByDesc('check_time')
            ->firstWhere('check_type', CheckType::CHECKOUT->value);

        if ($checkInRecord && $checkOutRecord) {
            $this->validateShiftNotCompleted($checkInRecord, $checkOutRecord, $requestTime, $context->date);
        }
    }

    /**
     * القاعدة 2: منع تكرار الدخول
     */
    private function validateDuplicateCheckIn(?string $requestType, bool $lastIsCheckIn): void
    {
        if ($requestType === CheckType::CHECKIN->value && $lastIsCheckIn) {
            throw new DuplicateCheckInException();
        }
    }

    /**
     * القاعدة 3: منع الخروج بدون دخول
     */
    private function validateMissingCheckIn(?string $requestType, object $context): void
    {
        if ($requestType === CheckType::CHECKOUT->value && (!$context->hasAnyCheckIn || $context->lastIsCheckOut)) {
            throw new MissingCheckInException();
        }
    }

    /**
     * القاعدة 4: التحقق قرب نهاية الشيفت بدون سجلات
     */
    private function validateNearShiftEndWithoutRecords(?string $requestType, object $context, Carbon $requestTime): void
    {
        if (!$context->hasAnyCheckIn && !$context->hasAnyCheckOut && $requestType === null && $context->shiftInfo) {
            $this->validateNearShiftEnd($context->shiftInfo, $requestTime);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // دوال مساعدة
    // ═══════════════════════════════════════════════════════════════

    /**
     * التحقق من أن الوردية لم تكتمل بعد
     */
    private function validateShiftNotCompleted($checkIn, $checkOut, Carbon $requestTime, string $date): void
    {
        $bounds = $this->shiftResolver->calculateBounds($checkIn->period, $checkIn->check_date);
        $isGraceExpired = $requestTime->gt($bounds['windowEnd']);

        if ($isGraceExpired) {
            throw new AttendanceCompletedException($date);
        }
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

    /**
     * تنسيق الوقت المتبقي بشكل مقروء
     */
    private function formatRemainingTime(int $totalSeconds): string
    {
        if ($totalSeconds >= 60) {
            $minutes = floor($totalSeconds / 60);
            $seconds = $totalSeconds % 60;

            if ($seconds > 0) {
                return $minutes . ' ' . __('notifications.minutue') . ' ' .
                    $seconds . ' ' . __('notifications.second');
            }

            return $minutes . ' ' . __('notifications.minutue');
        }

        return $totalSeconds . ' ' . __('notifications.second');
    }

    // ═══════════════════════════════════════════════════════════════
    // القاعدة 5: التحقق من الورديات المتداخلة
    // ═══════════════════════════════════════════════════════════════

    /**
     * التحقق من وجود ورديات متداخلة تتطلب اختيار المستخدم
     * 
     * الشروط:
     * - أكثر من وردية تطابق النافذة الزمنية
     * - لا يوجد أي سجل للموظف في أي من الورديات
     * - الوقت في منطقة الفجوة (بين نهاية الأولى وبداية الثانية)
     * 
     * @throws MultipleShiftsException
     */
    private function validateOverlappingShifts(
        Employee $employee,
        Carbon $requestTime,
        object $context,
        ?int $periodId
    ): void {
        // إذا تم تحديد period_id صراحةً، لا حاجة للتحقق
        if ($periodId !== null) {
            return;
        }

        // إذا يوجد أي سجل، لا حاجة للتحقق (الوردية محددة)
        if ($context->hasAnyCheckIn || $context->hasAnyCheckOut) {
            return;
        }

        // جلب جميع الورديات المطابقة
        $matchingShifts = $this->shiftResolver->getMatchingShifts($employee, $requestTime);

        // إذا وردية واحدة فقط أو أقل، لا تداخل
        if ($matchingShifts->count() <= 1) {
            return;
        }

        // التحقق: هل الوقت في منطقة الفجوة بين الورديتين؟
        if ($this->isTimeInGapZone($matchingShifts, $requestTime)) {
            throw new MultipleShiftsException(
                $this->buildShiftOptions($matchingShifts, $requestTime)
            );
        }
    }

    /**
     * التحقق: هل الوقت في منطقة الفجوة بين الورديتين؟
     * (بعد أو عند نهاية الأولى وقبل بداية الثانية)
     */
    private function isTimeInGapZone(Collection $matchingShifts, Carbon $requestTime): bool
    {
        // ترتيب الورديات حسب وقت البداية
        $sorted = $matchingShifts->sortBy(fn($m) => $m['bounds']['start']);
        $shifts = $sorted->values();

        // الحصول على نهاية الأولى وبداية الثانية
        $firstEnd = $shifts[0]['bounds']['end'];
        $secondStart = $shifts[1]['bounds']['start'];

        // الوقت في الفجوة: >= نهاية الأولى و < بداية الثانية
        return $requestTime->gte($firstEnd) && $requestTime->lt($secondStart);
    }

    /**
     * بناء قائمة خيارات الورديات للـ exception
     */
    private function buildShiftOptions(Collection $matchingShifts, Carbon $requestTime): Collection
    {
        return $matchingShifts->map(function ($match) use ($requestTime) {
            $period = $match['candidate']['period'];
            $bounds = $match['bounds'];

            // حساب حالة الوردية بالنسبة للوقت
            $status = $this->getShiftStatus($bounds, $requestTime);

            return [
                'period_id' => $period->id,
                'name' => $period->name ?? __('notifications.shift'),
                'start' => $bounds['start']->format('H:i'),
                'end' => $bounds['end']->format('H:i'),
                'status' => $status,
            ];
        });
    }

    /**
     * وصف حالة الوردية بالنسبة للوقت الحالي
     */
    private function getShiftStatus(array $bounds, Carbon $requestTime): string
    {
        if ($requestTime->lt($bounds['start'])) {
            $diff = $requestTime->diffInMinutes($bounds['start']);
            return __('notifications.starts_in_minutes', ['minutes' => $diff]);
        }

        if ($requestTime->gte($bounds['end'])) {
            $diff = $requestTime->diffInMinutes($bounds['end']);
            return __('notifications.ended_minutes_ago', ['minutes' => $diff]);
        }

        return __('notifications.currently_active');
    }
}
