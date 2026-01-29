<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Exceptions\DuplicateTimestampException;
use App\Modules\HR\Attendance\Services\Validator\Helpers\TimeFormatter;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;
use Carbon\Carbon;

/**
 * القاعدة 0: منع التسجيل في نفس الدقيقة بالضبط
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
        $lastMinute = $lastCheckTime->format('Y-m-d H:i');
        $currentMinute = $context->requestTime->format('Y-m-d H:i');

        if ($lastMinute === $currentMinute) {
            $nextMinute = $context->requestTime->copy()->addMinute()->startOfMinute();
            $remainingSeconds = $context->requestTime->diffInSeconds($nextMinute);
            $timeDisplay = TimeFormatter::formatRemainingTime($remainingSeconds);

            throw new DuplicateTimestampException(
                __('notifications.duplicate_timestamp_not_allowed', [
                    'seconds' => $timeDisplay
                ])
            );
        }
    }
}
