<?php

namespace App\Modules\HR\Attendance\Services\Validator\Rules;

use App\Modules\HR\Attendance\Contracts\ShiftResolverInterface;
use App\Modules\HR\Attendance\Enums\CheckType;
use App\Modules\HR\Attendance\Exceptions\AttendanceCompletedException;
use App\Modules\HR\Attendance\Services\Validator\ValidationContext;
use App\Modules\HR\Attendance\Services\Validator\ValidationRuleInterface;

/**
 * القاعدة 1: التحقق من اكتمال الوردية
 */
class ShiftCompletionRule implements ValidationRuleInterface
{
    public function __construct(
        private ShiftResolverInterface $shiftResolver
    ) {}

    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void
    {
        if (!($context->hasAnyCheckIn && $context->lastIsCheckOut)) {
            return;
        }

        $checkInRecord = $context->shiftRecords->firstWhere('check_type', CheckType::CHECKIN->value);
        $checkOutRecord = $context->shiftRecords
            ->sortByDesc('check_time')
            ->firstWhere('check_type', CheckType::CHECKOUT->value);

        if ($checkInRecord && $checkOutRecord) {
            $this->validateShiftNotCompleted($checkInRecord, $checkOutRecord, $context);
        }
    }

    private function validateShiftNotCompleted($checkIn, $checkOut, ValidationContext $context): void
    {
        $bounds = $this->shiftResolver->calculateBounds($checkIn->period, $checkIn->check_date);
        $isGraceExpired = $context->requestTime->gt($bounds['windowEnd']);

        if ($isGraceExpired) {
            throw new AttendanceCompletedException($context->date);
        }
    }
}
