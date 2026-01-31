<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Exceptions\DuplicateTimestampException;
use App\Modules\HR\Attendance\Services\Validator\Helpers\TimeFormatter;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;
use Carbon\Carbon;

/**
 * القاعدة 0: منع التسجيل خلال 15 دقيقة من آخر بصمة
 */
class DuplicateTimestampRule implements ValidationRuleInterface
{
    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        // تخطي الفحص إذا طُلب ذلك (للإضافة اليدوية من لوحة التحكم)
        if ($context->skipDuplicateTimestampCheck) {
            return;
        }

        if (!$context->lastRecord) {
            return;
        }

        $lastCheckTime = Carbon::parse($context->lastRecord->check_date . ' ' . $context->lastRecord->check_time);
        $allowedTime = $lastCheckTime->copy()->addMinutes(15);

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
