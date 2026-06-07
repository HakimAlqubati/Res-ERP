<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Exceptions\AttendanceCompletedException;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;
use Carbon\Carbon;

/**
 * قاعدة: التحقق الصارم من اكتمال الوردية (بناءً على وقت الخروج الرسمي)
 * 
 * إذا كان الموظف قد سجل خروج بعد انتهاء وقت الوردية الرسمي،
 * يعتبر الوردية منتهية تماماً ولا يُسمح بأي إجراءات أخرى.
 */
class StrictShiftCompletionRule implements ValidationRuleInterface
{
    public function __construct(
        private ShiftResolverInterface $shiftResolver
    ) {}

    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
         // يجب أن يكون هناك دورة مكتملة (دخول + خروج)
        if (!($context->hasAnyCheckIn && $context->lastIsCheckOut)) {
            return;
        }
        // جلب سجل الخروج الأخير
        $checkOutRecord = $context->shiftRecords
            ->sortByDesc('check_time')
            ->firstWhere('check_type', CheckType::CHECKOUT->value);

        $checkInRecord = $context->shiftRecords->firstWhere('check_type', CheckType::CHECKIN->value);

        if (!$checkOutRecord || !$checkInRecord) {
            return;
        }

        if (!$checkInRecord->period) {
            return;
        }

        // حساب حدود الوردية
        $bounds = $this->shiftResolver->calculateBounds($checkInRecord->period, $checkInRecord->check_date);

        // وقت الخروج المسجل الحقيقي بالاعتماد على real_check_date للتاريخ الدقيق بالتقويم
        $checkOutDate = $checkOutRecord->real_check_date ?? $checkOutRecord->check_date;
        $checkOutTime = Carbon::parse($checkOutDate . ' ' . $checkOutRecord->check_time);

        // آلية احتياطية (Fallback): إذا كان الشيفت ليلياً ومسجل خروج منطقياً قبل الدخول (عابر لمنتصف الليل) ولم يحدد real_check_date
        $checkInTime = Carbon::parse($checkInRecord->check_date . ' ' . $checkInRecord->check_time);
        if ($checkInRecord->period->day_and_night && $checkOutTime->lt($checkInTime)) {
            if (!$checkOutRecord->real_check_date || $checkOutRecord->real_check_date === $checkOutRecord->check_date) {
                $checkOutTime->addDay();
            }
        }

        // التحقق: هل الخروج تم بعد انتهاء الوردية؟
        // أو هل الوقت المطلوب يسبق وقت الخروج المسجل (محاولة تسجيل في فترة مغلقة)
        if ($checkOutTime->gte($bounds['end']) || $context->requestTime->lt($checkOutTime)) {
            $shiftName = $checkInRecord->period->name ?? 'Shift';
            throw new AttendanceCompletedException(
                $context->date,
                "{$shiftName} closed. Checkout recorded."
            );
        }
    }
}
