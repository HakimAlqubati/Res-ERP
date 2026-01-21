<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Exceptions\MultipleShiftsException;
use App\Modules\HR\Attendance\Services\Validator\Helpers\ShiftInfoBuilder;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;

/**
 * القاعدة 4: التحقق من الورديات المتداخلة
 */
class OverlappingShiftsRule implements ValidationRuleInterface
{
    public function __construct(
        private ShiftResolverInterface $shiftResolver
    ) {}

    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        // إذا تم تحديد period_id صراحةً، لا حاجة للتحقق
        if ($periodId !== null) {
            return;
        }

        // إذا يوجد أي سجل في أي وردية اليوم، لا حاجة للتحقق
        // (هذا يعني أن الموظف بدأ العمل في إحدى الورديات)
        if ($context->hasAnyDailyCheckIn || $context->hasAnyDailyCheckOut) {
            return;
        }

        // جلب جميع الورديات المطابقة
        $matchingShifts = $this->shiftResolver->getMatchingShifts($context->employee, $context->requestTime);

        // إذا وردية واحدة فقط أو أقل، لا تداخل
        if ($matchingShifts->count() <= 1) {
            return;
        }

        // التحقق: هل الوقت في منطقة الفجوة بين الورديتين؟
        if (ShiftInfoBuilder::isTimeInGapZone($matchingShifts, $context->requestTime)) {
            throw new MultipleShiftsException(
                ShiftInfoBuilder::buildShiftOptions($matchingShifts, $context->requestTime)
            );
        }
    }
}
