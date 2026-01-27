<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Exceptions\DuplicateCheckInException;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;

/**
 * القاعدة 2: منع تكرار الدخول
 */
class DuplicateCheckInRule implements ValidationRuleInterface
{
    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        if ($requestType === CheckType::CHECKIN->value && $context->lastIsCheckIn) {
            // if (isset($periodId)) {
            //     if ($periodId == $context->shiftInfo->getPeriodId()) {
            //         throw new DuplicateCheckInException();
            //     }
            // } else {
            //     throw new DuplicateCheckInException();
            // }
            throw new DuplicateCheckInException();
        }
    }
}
