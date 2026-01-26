<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Exceptions\TypeRequiredException;
use App\Modules\HR\Attendance\Services\AttendanceConfig;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;

/**
 * القاعدة 6: طلب تحديد النوع قرب نهاية الشيفت
 */
class NearShiftEndRule implements ValidationRuleInterface
{
    public function __construct(
        private AttendanceConfig $config
    ) {}

    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        // فقط إذا لا يوجد سجلات ولم يتم تحديد النوع
        if ($context->hasAnyCheckIn || $context->hasAnyCheckOut || $requestType !== null || !$context->shiftInfo) {
            return;
        }

        $hoursBeforePeriod = $this->config->getPreEndHoursForCheckInOut();
        $thresholdMinutes = $hoursBeforePeriod * 60;
        $thresholdTime = $context->shiftInfo->end->copy()->subMinutes($thresholdMinutes);

        if ($context->requestTime->gte($thresholdTime)) {
            throw new TypeRequiredException();
        }
    }
}
