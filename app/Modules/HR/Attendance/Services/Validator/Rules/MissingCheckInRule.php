<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Exceptions\MissingCheckInException;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;

/**
 * القاعدة 3: منع الخروج بدون دخول
 */
class MissingCheckInRule implements ValidationRuleInterface
{
    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        if ($requestType === CheckType::CHECKOUT->value && (!$context->hasAnyCheckIn || $context->lastIsCheckOut)) {
            throw new MissingCheckInException();
        }
    }
}
