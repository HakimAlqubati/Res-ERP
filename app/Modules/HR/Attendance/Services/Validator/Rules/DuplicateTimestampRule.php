<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Exceptions\DuplicateTimestampException;
use App\Modules\HR\Attendance\Services\Validator\Helpers\TimeFormatter;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;
use Carbon\Carbon;
use App\Models\Setting;

/**
 * القاعدة 0: منع التسجيل خلال 15 دقيقة من آخر بصمة
 */
class DuplicateTimestampRule implements ValidationRuleInterface
{
    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        // تخطي الفحص إذا طُلب ذلك (للإضافة اليدوية من لوحة التحكم أو طلبات الحضور)
        if ($context->skipDuplicateTimestampCheck || $context->isRequest) {
            return;
        }

        if (!$context->lastRecord) {
            return;
        }

        $lastCheckTime = Carbon::parse($context->lastRecord->check_date . ' ' . $context->lastRecord->check_time);

        // إذا كان وقت البصمة السابقة (بناءً على تاريخها المنطقي للشيفت) أكبر من وقت الطلب الحالي،
        // فهذا يعني أن البصمة السابقة تمت فعلياً في اليوم السابق (قبل منتصف الليل) ولكنها أخذت تاريخ الشيفت.
        // لذا نطرح يوماً لإعادتها لوقتها الفعلي منطقياً للمقارنة.

        if (
            $context->shiftInfo?->period?->start_at == '00:00:00' &&
            $lastCheckTime->greaterThan($context->requestTime)
        ) {
            $lastCheckTime->subDay();
        }

        $duplicateCheckMinutes = (int) Setting::getSetting('attendance_duplicate_check_minutes', 15);
        $allowedTime = $lastCheckTime->copy()->addMinutes($duplicateCheckMinutes);

        if ($context->requestTime->lessThan($allowedTime)) {
            $remainingSeconds = $context->requestTime->diffInSeconds($allowedTime);
            $timeDisplay = TimeFormatter::formatRemainingTime($remainingSeconds);

            throw new DuplicateTimestampException(
                __('notifications.duplicate_timestamp_not_allowed', [
                    'seconds' => $timeDisplay
                ])
            );
        }
    }
}
