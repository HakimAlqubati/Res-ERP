<?php

namespace App\Modules\HR\Attendance\Services\Validator;

/**
 * Interface لقواعد التحقق من الحضور
 */
interface ValidationRuleInterface
{
    /**
     * تطبيق قاعدة التحقق
     * 
     * @throws \App\Modules\HR\Attendance\Exceptions\AttendanceException
     */
    public function validate(ValidationContext $context, ?string $requestType = null, ?int $periodId = null): void;
}
