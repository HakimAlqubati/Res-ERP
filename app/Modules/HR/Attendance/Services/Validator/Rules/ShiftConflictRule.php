<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Exceptions\ShiftConflictException;
use App\Modules\HR\Attendance\Services\Validator\Helpers\ShiftInfoBuilder;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;

/**
 * القاعدة 5: التحقق من تعارض الورديات
 * 
 * check-in مفتوح في شيفت + الوقت ضمن شيفت أخرى
 */
class ShiftConflictRule implements ValidationRuleInterface
{
    public function __construct(
        private ShiftResolverInterface $shiftResolver
    ) {}

    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        // إذا تم تحديد period_id أو النوع صراحةً، لا حاجة للتحقق
        if ($periodId !== null || $requestType !== null) {
            return;
        }

        // التحقق: لا يوجد check-in مفتوح
        if (!$context->lastIsCheckIn) {
            return;
        }

        // جلب جميع الورديات المطابقة للوقت الحالي
        $matchingShifts = $this->shiftResolver->getMatchingShifts($context->employee, $context->requestTime);

        // إذا وردية واحدة فقط أو أقل، لا تعارض
        if ($matchingShifts->count() <= 1) {
            return;
        }

        // تحديد الشيفت المفتوحة (التي فيها check-in)
        $openShiftPeriodId = $context->shiftInfo?->getPeriodId();
        if (!$openShiftPeriodId) {
            return;
        }

        // البحث عن شيفت أخرى نشطة (الوقت ضمن نطاقها)
        $newShift = ShiftInfoBuilder::findActiveNewShift($matchingShifts, $openShiftPeriodId, $context->requestTime);
        if (!$newShift) {
            return;
        }

        // بناء معلومات الشيفت المفتوحة
        $openShift = ShiftInfoBuilder::findShiftByPeriodId($matchingShifts, $openShiftPeriodId);
        if (!$openShift) {
            return;
        }

        // رمي الاستثناء: يوجد تعارض!
        throw new ShiftConflictException(
            ShiftInfoBuilder::buildShiftInfo($openShift),
            ShiftInfoBuilder::buildShiftInfo($newShift)
        );
    }
}
